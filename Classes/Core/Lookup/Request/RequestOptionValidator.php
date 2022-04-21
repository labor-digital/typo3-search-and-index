<?php
/*
 * Copyright 2022 LABOR.digital
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * Last modified: 2022.04.14 at 18:59
 */

declare(strict_types=1);


namespace LaborDigital\T3sai\Core\Lookup\Request;


use LaborDigital\T3ba\Tool\TypoContext\TypoContextAwareTrait;
use LaborDigital\T3sai\Domain\Repository\DomainConfigRepository;
use Neunerlei\Options\Options;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

class RequestOptionValidator
{
    use TypoContextAwareTrait;
    
    /**
     * @var \LaborDigital\T3sai\Domain\Repository\DomainConfigRepository
     */
    protected $configRepository;
    
    /**
     * @var string|null
     */
    protected $siteError;
    
    /**
     * @var string|null
     */
    protected $languageError;
    
    /**
     * @var SiteInterface|null
     */
    protected $site;
    
    /**
     * @var SiteLanguage|null
     */
    protected $language;
    
    public function __construct(DomainConfigRepository $configRepository)
    {
        $this->configRepository = $configRepository;
    }
    
    /**
     * Prepares, validates and enriches the given options and returns them
     *
     * @param   array   $options  The options given to the request factory
     * @param   string  $type     The request type to validate the options for
     *
     * @return array
     */
    public function prepareOptions(array $options, string $type): array
    {
        $definition = [
            // The order of those keys is critical!
            'site' => $this->makeSiteDefinition(),
            'language' => $this->makeLanguageDefinition(),
            'domain' => $this->makeDomainDefinition(),
            'tags' => [
                'type' => ['null', 'array'],
                'default' => null,
                'validator' => static function (?array $tags) {
                    if (! $tags) {
                        return true;
                    }
                    
                    $c = 0;
                    foreach ($tags as $tag) {
                        $c++;
                        if (! is_string($tag)) {
                            return 'There is at least one invalid tag in your list. Tag at position: "' . $c . '" is not a string';
                        }
                    }
                    
                    return true;
                },
            ],
        ];
        
        if ($type === LookupRequest::TYPE_SEARCH) {
            $definition['contentMatchLength'] = [
                'type' => 'int',
                'default' => 400,
            ];
            $definition['maxTagItems'] = [
                'type' => ['int', 'null'],
                'default' => null,
            ];
        }
        
        if ($type === LookupRequest::TYPE_SEARCH || $type === LookupRequest::TYPE_SEARCH_COUNT) {
            $definition['additionalWhere'] = [
                'type' => 'string',
                'default' => '',
            ];
        }
        
        if ($type !== LookupRequest::TYPE_TAGS && $type !== LookupRequest::TYPE_SEARCH_COUNT) {
            $definition['maxItems'] = [
                'type' => ['int', 'null'],
                'default' => null,
            ];
            $definition['offset'] = [
                'type' => ['int', 'null'],
                'default' => null,
            ];
        }
        
        // Ensure that site, language and domain are always in the correct order
        foreach (['site', 'language', 'domain'] as $key) {
            $tmp = $options[$key] ?? null;
            unset($options[$key]);
            $options[$key] = $tmp;
            unset($tmp);
        }
        
        $this->reset();
        
        try {
            return Options::make($options, $definition);
        } finally {
            $this->reset();
        }
    }
    
    protected function makeSiteDefinition(): array
    {
        return [
            'default' => null,
            'preFilter' => function ($site) {
                $context = $this->getTypoContext();
                if ($site instanceof SiteInterface) {
                    return $site;
                }
                
                if ($site === null) {
                    return $context->site()->getCurrent();
                }
                
                if (! is_string($site)) {
                    $this->siteError = 'Invalid "site" value given, only site objects, or site identifiers are allowed';
                    
                    return null;
                }
                
                if (! $context->site()->has($site)) {
                    $this->siteError = 'Invalid "site" identifier: "' . $site . '" given, the site does not exist';
                    
                    return null;
                }
                
                return $context->site()->get($site);
            },
            'validator' => function ($v) {
                $this->site = $v;
                
                return $this->siteError ?? true;
            },
        ];
    }
    
    protected function makeLanguageDefinition(): array
    {
        return [
            'default' => null,
            'preFilter' => function ($language) {
                if ($language instanceof SiteLanguage) {
                    return $language;
                }
                
                // Site resolution failed -> skip this
                if ($this->site === null) {
                    return null;
                }
                
                if ($language === null) {
                    return $this->getTypoContext()->language()->getCurrentFrontendLanguage($this->site->getIdentifier());
                }
                
                if (is_string($language) || is_numeric($language)) {
                    foreach ($this->site->getLanguages() as $l) {
                        if ($l->getLanguageId() === $language ||
                            $l->getTwoLetterIsoCode() === strtolower((string)$language)) {
                            return $l;
                        }
                    }
                    
                    $this->languageError = 'Failed to resolve language: "' . $language . '" on site: "' . $this->site->getIdentifier() . '"';
                    
                    return null;
                }
                
                $this->languageError = 'Invalid "language" value given, only two-char iso codes, language uids or language objects are allowed';
                
                return null;
            },
            'validator' => function ($v) {
                $this->language = $v;
                
                return $this->languageError ?? true;
            },
        ];
    }
    
    protected function makeDomainDefinition(): array
    {
        return [
            'type' => 'string',
            'default' => null,
            'preFilter' => function ($domain) {
                if ($domain !== null) {
                    if (is_string($domain)) {
                        return preg_replace('~[^A-Za-z0-9\-_]~i', '', $domain);
                    }
                    
                    return $domain;
                }
                
                // Site resolution failed -> skip this
                if ($this->site === null) {
                    return '';
                }
                
                // Language resolution failed -> skip this
                if ($this->language === null) {
                    return '';
                }
                
                $domainConfig = $this->configRepository->findBestMatchingDomainConfig($this->site, $this->language);
                if (! $domainConfig) {
                    return 'NOT_FOUND: There is no registered search domain that matches your site: "' .
                           $this->site->getIdentifier() . '" and language: "' . $this->language->getTwoLetterIsoCode() . '" requirements';
                }
                
                return $domainConfig['identifier'];
            },
            'validator' => function ($v) {
                if (str_starts_with($v, 'NOT_FOUND')) {
                    return substr($v, 11);
                }
                
                return true;
            },
        ];
    }
    
    protected function reset(): void
    {
        $this->site = null;
        $this->language = null;
        $this->siteError = null;
        $this->languageError = null;
    }
}