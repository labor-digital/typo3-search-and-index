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
 * Last modified: 2022.04.14 at 16:48
 */

declare(strict_types=1);


namespace LaborDigital\T3sai\Core\Lookup\Request;

use LaborDigital\T3sai\Core\Lookup\Lexer\ParsedInput;
use LaborDigital\T3sai\Core\Soundex\SoundexGeneratorInterface;
use LaborDigital\T3sai\Core\StopWords\StopWordListInterface;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

class LookupRequest
{
    public const TYPE_SEARCH = 'search';
    public const TYPE_SEARCH_COUNT = 'search:count';
    public const TYPE_AUTOCOMPLETE = 'autocomplete';
    public const TYPE_TAGS = 'tags';
    
    public const ALLOWED_TYPES
        = [
            self::TYPE_SEARCH,
            self::TYPE_SEARCH_COUNT,
            self::TYPE_AUTOCOMPLETE,
            self::TYPE_TAGS,
        ];
    
    protected $type;
    protected $maxItems;
    protected $maxTagItems;
    protected $offset = 0;
    protected $additionalWhere;
    protected $contentMatchLength;
    protected $domainIdentifier;
    protected $domainConfig;
    
    /**
     * @var \LaborDigital\T3sai\Core\StopWords\StopWordListInterface
     */
    protected $stopWordList;
    
    /**
     * @var \LaborDigital\T3sai\Core\Soundex\SoundexGeneratorInterface
     */
    protected $soundexGenerator;
    
    /**
     * @var \TYPO3\CMS\Core\Database\Query\QueryBuilder
     */
    protected $queryBuilder;
    
    /**
     * @var \TYPO3\CMS\Core\Site\Entity\SiteInterface
     */
    protected $site;
    
    /**
     * @var \TYPO3\CMS\Core\Site\Entity\SiteLanguage
     */
    protected $language;
    
    /**
     * @var \LaborDigital\T3sai\Core\Lookup\Lexer\ParsedInput
     */
    protected $input;
    
    public function __construct(
        string $type,
        string $domainIdentifier,
        array $domainConfig,
        ?int $maxItems,
        ?int $maxTagItems,
        int $offset,
        ?string $additionalWhere,
        ?int $contentMatchLength,
        StopWordListInterface $stopWordList,
        SoundexGeneratorInterface $soundexGenerator,
        QueryBuilder $queryBuilder,
        SiteInterface $site,
        SiteLanguage $language,
        ParsedInput $input
    )
    {
        $this->type = $type;
        $this->domainIdentifier = $domainIdentifier;
        $this->domainConfig = $domainConfig;
        $this->maxItems = $maxItems;
        $this->maxTagItems = $maxTagItems;
        $this->offset = $offset;
        $this->additionalWhere = $additionalWhere;
        $this->contentMatchLength = $contentMatchLength;
        $this->stopWordList = $stopWordList;
        $this->soundexGenerator = $soundexGenerator;
        $this->queryBuilder = $queryBuilder;
        $this->site = $site;
        $this->language = $language;
        $this->input = $input;
    }
    
    /**
     * Returns the type of request to be served.
     * The content is one of the TYPE_ constants
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }
    
    /**
     * Returns the maximal number of search results returned by this method.
     * By default, (NULL) all search results will be returned only limited by "maxTagItems".
     * WARNING: If "maxTagItems" is set, this setting MUST be ignored
     *
     * @return int|null
     */
    public function getMaxItems(): ?int
    {
        return $this->maxItems;
    }
    
    /**
     * Returns the maximal number of search results returned for a single tag.
     * WARNING: This overrides "maxItems"
     *
     * @return int|null
     */
    public function getMaxTagItems(): ?int
    {
        return $this->maxTagItems;
    }
    
    /**
     * Returns the offset from the top of the result list to return.
     * WARNING: If "maxTagItems" is set, this setting MUST be ignored
     *
     * @return int
     */
    public function getOffset(): int
    {
        return $this->offset;
    }
    
    /**
     * Additional "WHERE" condition which limits the number of outputted results.
     * MUST start with an OR, or an AND. NULL if there is no query.
     * All fields MUST be resolved through the `r`.`field` alias.
     * Your code MUST be sanitized!
     *
     * @return string|null
     */
    public function getAdditionalWhere(): ?string
    {
        return $this->additionalWhere;
    }
    
    /**
     * The max number of characters the contentMatch result field should contain
     *
     * @return int|null
     */
    public function getContentMatchLength(): ?int
    {
        return $this->contentMatchLength;
    }
    
    /**
     * Returns the unique identifier of the domain this request processes
     *
     * @return string
     */
    public function getDomainIdentifier(): string
    {
        return $this->domainIdentifier;
    }
    
    /**
     * Returns the configuration array for the search domain this request processes
     *
     * @return array
     */
    public function getDomainConfig(): array
    {
        return $this->domainConfig;
    }
    
    /**
     * Returns the selected stop word list that was resolved for this request
     *
     * @return \LaborDigital\T3sai\Core\StopWords\StopWordListInterface
     */
    public function getStopWordList(): StopWordListInterface
    {
        return $this->stopWordList;
    }
    
    /**
     * Returns the soundex generator that was resolved for this request
     *
     * @return \LaborDigital\T3sai\Core\Soundex\SoundexGeneratorInterface
     */
    public function getSoundexGenerator(): SoundexGeneratorInterface
    {
        return $this->soundexGenerator;
    }
    
    /**
     * Returns the TYPO3 SQL Query builder for the database the request should be handled with
     *
     * @return \TYPO3\CMS\Core\Database\Query\QueryBuilder
     */
    public function getQueryBuilder(): QueryBuilder
    {
        return $this->queryBuilder;
    }
    
    /**
     * Returns the TYPO3 site instance for which this request is being processed
     *
     * @return \TYPO3\CMS\Core\Site\Entity\SiteInterface
     */
    public function getSite(): SiteInterface
    {
        return $this->site;
    }
    
    /**
     * Returns the site language object processed by this request
     *
     * @return \TYPO3\CMS\Core\Site\Entity\SiteLanguage
     */
    public function getLanguage(): SiteLanguage
    {
        return $this->language;
    }
    
    /**
     * Returns the parsed, and tokenized user input which lead to the request
     *
     * @return \LaborDigital\T3sai\Core\Lookup\Lexer\ParsedInput
     */
    public function getInput(): ParsedInput
    {
        return $this->input;
    }
}