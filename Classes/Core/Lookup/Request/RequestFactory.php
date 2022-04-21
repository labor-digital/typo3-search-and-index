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
 * Last modified: 2022.04.14 at 17:22
 */

declare(strict_types=1);


namespace LaborDigital\T3sai\Core\Lookup\Request;


use InvalidArgumentException;
use LaborDigital\T3ba\Core\Di\ContainerAwareTrait;
use LaborDigital\T3ba\Tool\Database\DbService;
use LaborDigital\T3sai\Core\Lookup\Lexer\InputLexer;
use LaborDigital\T3sai\Core\Soundex\SoundexGeneratorFactory;
use LaborDigital\T3sai\Core\StopWords\StopWordListFactory;
use LaborDigital\T3sai\Core\Translation\Tags\TagTranslationProvider;
use LaborDigital\T3sai\Domain\Repository\DomainConfigRepository;
use LaborDigital\T3sai\Domain\Repository\SearchRepository;
use LaborDigital\T3sai\Event\LookupInputFilterEvent;
use LaborDigital\T3sai\Event\LookupRequestFilterEvent;
use Psr\EventDispatcher\EventDispatcherInterface;

class RequestFactory
{
    use ContainerAwareTrait;
    
    /**
     * @var \LaborDigital\T3sai\Core\Lookup\Request\RequestOptionValidator
     */
    protected $optionValidator;
    
    /**
     * @var \LaborDigital\T3sai\Core\Soundex\SoundexGeneratorFactory
     */
    protected $soundexGeneratorFactory;
    
    /**
     * @var \LaborDigital\T3sai\Core\StopWords\StopWordListFactory
     */
    protected $stopWordListFactory;
    
    /**
     * @var \LaborDigital\T3ba\Tool\Database\DbService
     */
    protected $dbService;
    
    /**
     * @var \Psr\EventDispatcher\EventDispatcherInterface
     */
    protected $eventDispatcher;
    
    /**
     * @var \LaborDigital\T3sai\Core\Lookup\Lexer\InputLexer
     */
    protected $lexer;
    
    /**
     * @var \LaborDigital\T3sai\Core\Translation\Tags\TagTranslationProvider
     */
    protected $tagTranslator;
    
    /**
     * @var \LaborDigital\T3sai\Domain\Repository\DomainConfigRepository
     */
    protected $configRepository;
    
    public function __construct(
        RequestOptionValidator $optionValidator,
        SoundexGeneratorFactory $soundexGeneratorFactory,
        StopWordListFactory $stopWordListFactory,
        DbService $dbService,
        EventDispatcherInterface $eventDispatcher,
        InputLexer $lexer,
        TagTranslationProvider $tagTranslator,
        DomainConfigRepository $configRepository
    )
    {
        $this->optionValidator = $optionValidator;
        $this->soundexGeneratorFactory = $soundexGeneratorFactory;
        $this->stopWordListFactory = $stopWordListFactory;
        $this->dbService = $dbService;
        $this->eventDispatcher = $eventDispatcher;
        $this->lexer = $lexer;
        $this->tagTranslator = $tagTranslator;
        $this->configRepository = $configRepository;
    }
    
    /**
     * Creates a new lookup request instance based on the parsed input,
     * lookup type and provided options
     *
     * @param   string|null  $input    The input/search string to make the request for
     * @param   string       $type     One of the LookupRequest::TYPE_ constants
     * @param   array        $options  Additional options, which depend on the input type
     *
     * @return \LaborDigital\T3sai\Core\Lookup\Request\LookupRequest
     */
    public function makeRequest(?string $input, string $type, array $options): LookupRequest
    {
        if (! in_array($type, LookupRequest::ALLOWED_TYPES, true)) {
            throw new InvalidArgumentException('Invalid request-type: "' . $type . '" given to the factory');
        }
        
        return $this->eventDispatcher->dispatch(
            new LookupRequestFilterEvent(
                $this->makeInstance(LookupRequest::class, $this->makeRequestArgs($input, $type, $options))
            )
        )->getRequest();
    }
    
    /**
     * Resolves the list of arguments to be provided to the request constructor
     *
     * @param   string|null  $input
     * @param   string       $type
     * @param   array        $options
     *
     * @return array
     */
    protected function makeRequestArgs(?string $input, string $type, array $options): array
    {
        $options = $this->optionValidator->prepareOptions($options, $type);
        /** @var \TYPO3\CMS\Core\Site\Entity\SiteInterface $site */
        $site = $options['site'];
        /** @var \TYPO3\CMS\Core\Site\Entity\SiteLanguage $language */
        $language = $options['language'];
        $domainIdentifier = $options['domain'];
        $domainConfig = $this->configRepository->findDomainConfig($domainIdentifier, $site);
        
        $parsedInput = $this->lexer->parse($input ?? '');
        if ($input !== null) {
            $this->tagTranslator->resolveTranslationsInParsedInput($domainConfig, $parsedInput);
            $parsedInput = $this->eventDispatcher->dispatch(new LookupInputFilterEvent($parsedInput))->getInput();
        }
        
        // If we got tags in our options they take precedence over the tags provided by the search query
        if (! empty($options['tags'])) {
            $parsedInput->requiredTags = $options['tags'];
            $parsedInput->deniedTags = array_diff($parsedInput->deniedTags, $options['tags']);
        }
        
        return [
            $type,
            $domainIdentifier,
            $domainConfig,
            $options['maxItems'] ?? null,
            $options['maxTagItems'] ?? null,
            $options['offset'] ?? 0,
            $options['additionalWhere'] ?? null,
            $options['contentMatchLength'] ?? null,
            $this->stopWordListFactory->makeStopWordList($domainConfig, $language->getTwoLetterIsoCode()),
            $this->soundexGeneratorFactory->makeSoundexGenerator($domainConfig, $language->getTwoLetterIsoCode()),
            $this->dbService->getQueryBuilder(SearchRepository::TABLE_NODES),
            $site,
            $language,
            $parsedInput,
        ];
    }
}