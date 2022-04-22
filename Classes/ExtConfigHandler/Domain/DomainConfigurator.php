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
 * Last modified: 2022.04.11 at 12:25
 */

declare(strict_types=1);


namespace LaborDigital\T3sai\ExtConfigHandler\Domain;


use LaborDigital\T3ba\Core\Di\NoDiInterface;
use LaborDigital\T3ba\ExtConfig\SiteBased\SiteConfigContext;
use LaborDigital\T3sai\ExtConfigHandler\Domain\Internal\IndexerList;
use LaborDigital\T3sai\ExtConfigHandler\Domain\Internal\LanguageClassList;
use LaborDigital\T3sai\ExtConfigHandler\Domain\Internal\SiteMapConfigurator;
use Neunerlei\Configuration\State\ConfigState;

class DomainConfigurator implements NoDiInterface
{
    /**
     * @var string
     */
    protected $domainIdentifier;
    
    /**
     * @var \LaborDigital\T3ba\ExtConfig\SiteBased\SiteConfigContext
     */
    protected $context;
    
    /**
     * @var \LaborDigital\T3sai\ExtConfigHandler\Domain\Internal\IndexerList
     */
    protected $recordIndexers;
    
    /**
     * @var \LaborDigital\T3sai\ExtConfigHandler\Domain\Internal\IndexerList
     */
    protected $contentElementIndexers;
    
    /**
     * @var \LaborDigital\T3sai\ExtConfigHandler\Domain\Internal\SiteMapConfigurator
     */
    protected $siteMapConfig;
    
    /**
     * @var array|null
     */
    protected $allowedLanguages;
    
    /**
     * @var \LaborDigital\T3sai\ExtConfigHandler\Domain\Internal\LanguageClassList
     */
    protected $stopWordLists;
    
    /**
     * @var \LaborDigital\T3sai\ExtConfigHandler\Domain\Internal\LanguageClassList
     */
    protected $soundexGenerators;
    
    protected $tagTranslations = [];
    
    public function __construct(
        string $domainIdentifier,
        SiteConfigContext $context,
        IndexerList $recordTransformers,
        IndexerList $contentElementTransformers,
        SiteMapConfigurator $siteMapConfig,
        LanguageClassList $stopWordLists,
        LanguageClassList $soundexGenerators
    )
    {
        $this->domainIdentifier = $domainIdentifier;
        $this->context = $context;
        $this->recordIndexers = $recordTransformers;
        $this->contentElementIndexers = $contentElementTransformers;
        $this->siteMapConfig = $siteMapConfig;
        $this->stopWordLists = $stopWordLists;
        $this->soundexGenerators = $soundexGenerators;
        
        $this->addTagTranslation('page', 't3sai.tag.page', 't3sai.tag.page.input');
    }
    
    /**
     * Returns the unique name of the search domain that should be configured here
     *
     * @return string
     */
    public function getDomainIdentifier(): string
    {
        return $this->domainIdentifier;
    }
    
    /**
     * Returns the list on which you can register record transformer classes
     *
     * @return \LaborDigital\T3sai\ExtConfigHandler\Domain\Internal\IndexerList
     * @see \LaborDigital\T3sai\Search\Indexer\RecordIndexerInterface
     */
    public function getRecordIndexers(): IndexerList
    {
        return $this->recordIndexers;
    }
    
    /**
     * Returns the list of content element transformer classes for this domain
     *
     * @return \LaborDigital\T3sai\ExtConfigHandler\Domain\Internal\IndexerList
     * @see \LaborDigital\T3sai\Search\Indexer\Page\ContentElement\ContentElementIndexerInterface
     */
    public function getContentElementIndexers(): IndexerList
    {
        return $this->contentElementIndexers;
    }
    
    /**
     * Allows you to configure additional settings for the sitemap generation
     *
     * @return \LaborDigital\T3sai\ExtConfigHandler\Domain\Internal\SiteMapConfigurator
     */
    public function getSiteMapConfig(): SiteMapConfigurator
    {
        return $this->siteMapConfig;
    }
    
    /**
     * Allows you to set the language codes (two char iso code) that should be enabled for this domain.
     * If you set an empty array as languages this domain will serve all languages
     *
     * @param   array|null  $languages  A list of language codes to be allowed or null to allow alll langauges
     *
     * @return \LaborDigital\T3sai\ExtConfigHandler\Domain\DomainConfigurator
     */
    public function setAllowedLanguages(?array $languages): self
    {
        $this->allowedLanguages = array_unique($languages);
        
        return $this;
    }
    
    /**
     * Returns the list of language keys that are allowed in this search domain.
     * The result is either a list of two letter language codes or null, which means the domain can serve all languages
     *
     * @return array|null
     */
    public function getAllowedLanguages(): ?array
    {
        return $this->allowedLanguages;
    }
    
    /**
     * Returns the list of stop-word lists by their language key
     * If the list is empty, the built-in lists will be used.
     *
     * @return \LaborDigital\T3sai\ExtConfigHandler\Domain\Internal\LanguageClassList
     */
    public function getStopWordLists(): LanguageClassList
    {
        return $this->stopWordLists;
    }
    
    /**
     * Returns the list of soundex generators by their language key.
     * If the list is empty, the built-in generators will be used
     *
     * @return \LaborDigital\T3sai\ExtConfigHandler\Domain\Internal\LanguageClassList
     */
    public function getSoundexGenerators(): LanguageClassList
    {
        return $this->soundexGenerators;
    }
    
    /**
     * Registers a new tag translation for this domain. Tag translations can target the output, as well as the "@tagLabel"
     * notation in the input string
     *
     * @param   string       $tag           The programmatic id/name of the tag you want to translate
     * @param   string       $label         The translation label when the tag is rendered to the screen.
     *                                      Can be either a translation label or a hardcoded string.
     * @param   string|null  $inputLabel    The translation label when the tag is parsed from the "@tagLabel" notation
     *                                      in the input string. Setting this is optional. If omitted the $tag value will be used.
     *                                      Can be either a translation label or a hardcoded string.
     *                                      Note: The lexer is case-in-sensitive when reading the label!
     *
     * @return $this
     */
    public function addTagTranslation(string $tag, string $label, ?string $inputLabel = null): self
    {
        $this->tagTranslations[$tag] = [
            'label' => $label,
            'inputLabel' => $inputLabel ?? $tag,
        ];
        
        return $this;
    }
    
    /**
     * Removes a previously registered tag translation from this domain.
     *
     * @param   string  $tag  The programmatic id/name of the tag to remove the registered labels for
     *
     * @return $this
     */
    public function removeTagTranslation(string $tag): self
    {
        unset($this->tagTranslations[$tag]);
        
        return $this;
    }
    
    /**
     * Returns the list of all registered tag translations ordered by their language keys
     *
     * @return array
     */
    public function getTagTranslations(): array
    {
        return $this->tagTranslations;
    }
    
    public function finish(ConfigState $state): void
    {
        $state->setAsJson($this->domainIdentifier, [
            'identifier' => $this->domainIdentifier,
            'allowedLanguages' => is_array($this->allowedLanguages) ?
                array_map('strtolower', $this->allowedLanguages) : null,
            'tagTranslations' => $this->tagTranslations,
            'stopWords' => $this->stopWordLists->getAll(),
            'soundex' => $this->soundexGenerators->getAll(),
            'indexer' => [
                'record' => $this->recordIndexers->getAll(),
                'contentElement' => $this->contentElementIndexers->getAll(),
            ],
            'siteMap' => $this->siteMapConfig->asArray(),
        ]);
    }
}