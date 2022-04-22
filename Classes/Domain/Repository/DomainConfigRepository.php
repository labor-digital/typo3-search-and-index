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
 * Last modified: 2022.04.21 at 14:41
 */

declare(strict_types=1);


namespace LaborDigital\T3sai\Domain\Repository;


use LaborDigital\T3ba\Tool\OddsAndEnds\SerializerUtil;
use LaborDigital\T3ba\Tool\TypoContext\TypoContextAwareTrait;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

class DomainConfigRepository implements SingletonInterface
{
    use TypoContextAwareTrait;
    
    /**
     * Stores the resolved list of domain configurations
     *
     * @var array|null
     */
    protected $cache;
    
    /**
     * Returns the list of ALL search domain configurations
     * ordered by their site key they have been registered for.
     *
     * @return array
     */
    public function findAllDomainConfigs(): array
    {
        if (isset($this->cache)) {
            return $this->cache;
        }
        
        $config = [];
        $siteList = $this->getTypoContext()->config()->getConfigValue('typo.site.*.t3sai');
        foreach ($siteList as $siteIdentifier => $subSet) {
            if (! is_array($subSet['domains'] ?? null)) {
                continue;
            }
            
            foreach ($subSet['domains'] as $domainIdentifier => $domainConfig) {
                $config[$siteIdentifier][$domainIdentifier]
                    = SerializerUtil::unserializeJson($domainConfig);
            }
        }
        
        return $this->cache = $config;
    }
    
    /**
     * Tries to find the best matching domain configuration for the given site
     * and language instance.
     *
     * @param   \TYPO3\CMS\Core\Site\Entity\SiteInterface  $site
     * @param   \TYPO3\CMS\Core\Site\Entity\SiteLanguage   $language
     *
     * @return array|null
     */
    public function findBestMatchingDomainConfig(SiteInterface $site, SiteLanguage $language): ?array
    {
        $domains = $this->findDomainsForSite($site);
        $domains = $this->filterDomainsByLanguage($domains, $language);
        
        if (empty($domains)) {
            return null;
        }
        
        return reset($domains);
    }
    
    /**
     * Tries to resolve the search domain configuration for the provided identifier.
     *
     * @param   string              $domainIdentifier  The domain identifier for which the config should be found
     * @param   SiteInterface|null  $site              The site in which the domain should be searched.
     *                                                 If omitted, the current site will be used
     * @param   SiteLanguage|null   $language          Optional language constraint to be matched when resolving
     *                                                 the configuration. If omitted the language requirements
     *                                                 will be IGNORED!
     *
     * @return array|null
     */
    public function findDomainConfig(string $domainIdentifier, ?SiteInterface $site = null, ?SiteLanguage $language = null): ?array
    {
        $domains = $this->findDomainsForSite($site);
        
        if ($language) {
            $domains = $this->filterDomainsByLanguage($domains, $language);
        }
        
        return $domains[$domainIdentifier] ?? null;
    }
    
    /**
     * Resolves all domains including their configurations for the given site
     * If no site is given, the current site is used instead
     *
     * @param   SiteInterface|null      $site       The site in which the domain should be searched.
     *                                              If omitted, the current site will be used
     * @param   SiteLanguage|bool|null  $language   Optional language constraint to be matched when resolving
     *                                              the configuration. If omitted the language requirements
     *                                              will be IGNORED! If TRUE is passed as value, the current
     *                                              language will be used as constraint.
     *
     * @return array
     */
    public function findDomainsForSite(?SiteInterface $site, $language = null): array
    {
        $site = $site ?? $this->getTypoContext()->site()->getCurrent();
        $domains = $this->findAllDomainConfigs()[$site->getIdentifier()] ?? [];
        
        if ($language) {
            if (! $language instanceof SiteLanguage) {
                $language = $this->getTypoContext()->language()->getCurrentFrontendLanguage();
            }
            
            $domains = $this->filterDomainsByLanguage($domains, $language);
        }
        
        return $domains;
    }
    
    /**
     * Flushes the internal lookup cache to force it to be rebuild on the next request
     *
     * @return void
     */
    public function flushCache(): void
    {
        $this->cache = null;
    }
    
    /**
     * Filters the given list of domains and returns only domains that match the given language requirement
     *
     * @param   array                                     $domains
     * @param   \TYPO3\CMS\Core\Site\Entity\SiteLanguage  $language
     *
     * @return array
     */
    protected function filterDomainsByLanguage(array $domains, SiteLanguage $language): array
    {
        $filtered = [];
        foreach ($domains as $domainIdentifier => $domainConfig) {
            if (! $domainConfig['allowedLanguages'] ||
                in_array($language->getTwoLetterIsoCode(), $domainConfig['allowedLanguages'], true)) {
                $filtered[$domainIdentifier] = $domainConfig;
            }
        }
        
        return $filtered;
    }
}