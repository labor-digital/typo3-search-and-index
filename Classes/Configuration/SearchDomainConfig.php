<?php
/**
 * Copyright 2020 LABOR.digital
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
 * Last modified: 2020.07.16 at 17:10
 */

declare(strict_types=1);
/**
 * Copyright 2019 LABOR.digital
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
 * Last modified: 2019.09.02 at 17:46
 */

namespace LaborDigital\T3SAI\Configuration;


use Composer\Autoload\ClassMapGenerator;
use LaborDigital\T3SAI\Builtin\Transformer\Page\ContentElement\ContentElementTransformerInterface;
use LaborDigital\T3SAI\Exception\SearchAndIndexException;
use LaborDigital\T3SAI\Indexer\Transformer\RecordTransformerInterface;
use LaborDigital\Typo3BetterApi\ExtConfig\ExtConfigContext;
use Neunerlei\FileSystem\Fs;
use Neunerlei\Options\Options;

class SearchDomainConfig
{
    
    /**
     * Holds the context instance while in the configuration class. Is null afterwards
     *
     * @var \LaborDigital\Typo3BetterApi\ExtConfig\ExtConfigContext|null
     */
    protected $context;
    
    /**
     * The identifier of the TYPO3 site this domain applies to
     *
     * @var string
     */
    protected $siteIdentifier = 'default';
    
    /**
     * The list of registered record transformers in this domain
     *
     * @var array
     * @see \LaborDigital\T3SAI\Indexer\RecordTransformerInterface
     */
    protected $recordTransformers = [];
    
    /**
     * The list of registered content element transformer classes.
     * Content element transformers are used to convert contents of the tt_content table into something
     * useful that we can find for our user.
     *
     * @var array
     * @see \LaborDigital\T3SAI\Builtin\Transformer\Page\ContentElement\ContentElementTransformerInterface
     */
    protected $contentElementTransformers = [];
    
    /**
     * The list of registered stop word checker classes.
     * As 2 char language iso-code and the checker class name as key value pairs
     *
     * @var array
     * @see \LaborDigital\T3SAI\StopWords\StopWordCheckerInterface
     */
    protected $stopWordCheckers = [];
    
    /**
     * A list of 2 char language iso-codes for languages that should be included into the index.
     * If this is null all languages will end up in the index.
     *
     * @var array|null
     */
    protected $languages = [];
    
    /**
     * The configuration for the news data transformer
     *
     * @var \LaborDigital\T3SAI\Configuration\NewsDataTransformerConfig
     */
    protected $newsTransformerConfig;
    
    /**
     * The configuration for the page data transformer
     *
     * @var \LaborDigital\T3SAI\Configuration\PageDataTransformerConfig
     */
    protected $pageTransformerConfig;
    
    /**
     * The list of options for the siteMap generator
     *
     * @var array
     */
    protected $siteMapOptions = [];
    
    /**
     * SearchDomainConfig constructor.
     *
     * @param   \LaborDigital\Typo3BetterApi\ExtConfig\ExtConfigContext  $context
     */
    public function __construct(ExtConfigContext $context)
    {
        $this->context               = $context;
        $this->newsTransformerConfig = new NewsDataTransformerConfig();
        $this->pageTransformerConfig = new PageDataTransformerConfig();
    }
    
    /**
     * Remove the context reference before serializing the object
     */
    public function __sleep()
    {
        $vars = get_object_vars($this);
        unset($vars['context']);
        
        return array_keys($vars);
    }
    
    /**
     * Returns the ext config object when the search configuration is collected.
     * This method returns null when the cached configuration object is used in the indexer
     *
     * @return \LaborDigital\Typo3BetterApi\ExtConfig\ExtConfigContext|null
     */
    public function getContext(): ?ExtConfigContext
    {
        return $this->context;
    }
    
    /**
     * Returns the configuration for the news data transformer
     *
     * @return \LaborDigital\T3SAI\Configuration\NewsDataTransformerConfig
     */
    public function getNewsTransformerConfig(): NewsDataTransformerConfig
    {
        return $this->newsTransformerConfig;
    }
    
    /**
     * Returns the configuration for the page data transformer
     *
     * @return \LaborDigital\T3SAI\Configuration\PageDataTransformerConfig
     */
    public function getPageTransformerConfig(): PageDataTransformerConfig
    {
        return $this->pageTransformerConfig;
    }
    
    /**
     * Returns the list of currently registered record transformer classes as an array
     *
     * @return array
     */
    public function getRecordTransformers(): array
    {
        return $this->recordTransformers;
    }
    
    /**
     * Allows you to set the list of registered record transformers to the given list of classes.
     * The classes should implement the RecordTransformerInterface interface
     *
     * @param   array  $classes
     *
     * @return \LaborDigital\T3SAI\Configuration\SearchDomainConfig
     * @see \LaborDigital\T3SAI\Indexer\RecordTransformerInterface
     * @see RecordTransformerInterface
     */
    public function setRecordTransformers(array $classes): SearchDomainConfig
    {
        $this->recordTransformers = [];
        foreach ($classes as $class) {
            $this->registerRecordTransformer($class);
        }
        
        return $this;
    }
    
    /**
     * This helper adds a whole directory of record transformers to the list.
     * It will add all classes that implement the RecordTransformerInterface
     *
     * @param   string  $directory  The path to the directory to add the transformers from.
     *                              Can be an EXT:... path
     *
     * @return $this
     * @see RecordTransformerInterface
     */
    public function registerRecordTransformersInDirectory(string $directory = 'EXT:{{extkey}}/Classes/Search'): SearchDomainConfig
    {
        $directory = $this->context->TypoContext->Path()->typoPathToRealPath(
            (string)$this->context->replaceMarkers($directory));
        $map       = ClassMapGenerator::createMap(Fs::getDirectoryIterator($directory));
        foreach ($map as $class => $filename) {
            if (in_array(RecordTransformerInterface::class, class_implements($class), true)) {
                $this->recordTransformers[] = $class;
            }
        }
        $this->recordTransformers = array_unique($this->recordTransformers);
        
        return $this;
    }
    
    /**
     * Registers a new record transformer in the list
     *
     * @param   string  $transformerClass  A class that implements the RecordTransformerInterface
     *
     * @return \LaborDigital\T3SAI\Configuration\SearchDomainConfig
     * @throws \LaborDigital\T3SAI\Exception\SearchAndIndexException
     * @see RecordTransformerInterface
     */
    public function registerRecordTransformer(string $transformerClass): SearchDomainConfig
    {
        if (! in_array(RecordTransformerInterface::class, class_implements($transformerClass), true)) {
            throw new SearchAndIndexException(
                'The given record transformer $transformerClass does not implement the required interface: ' .
                RecordTransformerInterface::class);
        }
        $this->recordTransformers[] = $transformerClass;
        $this->recordTransformers   = array_unique($this->recordTransformers);
        
        return $this;
    }
    
    /**
     * Returns the list of currently registered content element transformer classes as an array
     *
     * @return array
     */
    public function getContentElementTransformers(): array
    {
        return $this->contentElementTransformers;
    }
    
    /**
     * Allows you to set the list of registered content element transformers to the given list of classes.
     * The classes should implement the ContentElementTransformerInterface interface
     *
     * @param   array  $classes
     *
     * @return \LaborDigital\T3SAI\Configuration\SearchDomainConfig
     * @see ContentElementTransformerInterface
     */
    public function setContentElementTransformers(array $classes): SearchDomainConfig
    {
        $this->contentElementTransformers = [];
        foreach ($classes as $class) {
            $this->registerContentElementTransformer($class);
        }
        
        return $this;
    }
    
    /**
     * Registers a new content element transformer class.
     * Content element transformers are used to convert contents from the tt_content table into something
     * useful that we can find for our user.
     *
     * @param   string  $transformerClass
     *
     * @return \LaborDigital\T3SAI\Configuration\SearchDomainConfig
     * @throws \LaborDigital\T3SAI\Exception\SearchAndIndexException
     * @see ContentElementTransformerInterface
     */
    public function registerContentElementTransformer(string $transformerClass): SearchDomainConfig
    {
        if (! in_array($transformerClass, class_implements(ContentElementTransformerInterface::class), true)) {
            throw new SearchAndIndexException(
                'The given content element transformer $transformerClass does not implement the required interface: ' .
                ContentElementTransformerInterface::class);
        }
        if (! in_array($transformerClass, $this->contentElementTransformers, true)) {
            $this->contentElementTransformers[] = $transformerClass;
        }
        
        return $this;
    }
    
    /**
     * This helper adds a whole directory of content element data transformers into the list.
     *
     * @param   string  $directory
     *
     * @return $this
     * @see ContentElementTransformerInterface
     */
    public function registerContentElementTransformersInDirectory(string $directory = 'EXT:{{extkey}}/Classes/Search/ContentElement'): self
    {
        $directory = $this->context->TypoContext->Path()->typoPathToRealPath(
            (string)$this->context->replaceMarkers($directory));
        $map       = ClassMapGenerator::createMap(Fs::getDirectoryIterator($directory));
        foreach ($map as $class => $filename) {
            if (in_array(ContentElementTransformerInterface::class, class_implements($class), true)) {
                $this->contentElementTransformers[] = $class;
            }
        }
        $this->contentElementTransformers = array_unique($this->contentElementTransformers);
        
        return $this;
    }
    
    /**
     * Returns the list of stop-word checkers by their language key
     *
     * @return array
     */
    public function getStopWordCheckers(): array
    {
        return $this->stopWordCheckers;
    }
    
    /**
     * Registers a new stop-word checker class to for the domain.
     * There can only be one stop word checker class for each language.
     *
     * @param   string  $language      The language key as two char iso code
     * @param   string  $checkerClass  The class to register. The class has to to implement the StopWordCheckerInterface
     *
     * @return \LaborDigital\T3SAI\Configuration\SearchDomainConfig
     * @throws \LaborDigital\T3SAI\Exception\SearchAndIndexException
     * @see \LaborDigital\T3SAI\StopWords\StopWordCheckerInterface
     */
    public function registerStopWordChecker(string $language, string $checkerClass): SearchDomainConfig
    {
        if (strlen($language) !== 2) {
            throw new SearchAndIndexException('Invalid language key given!');
        }
        $this->stopWordCheckers[$language] = $checkerClass;
        
        return $this;
    }
    
    /**
     * Returns the list of language keys that are allowed in this search domain.
     * The result is either a list of two letter language codes or empty,
     * which means the
     *
     * @return array
     */
    public function getAllowedLanguages(): array
    {
        return $this->languages;
    }
    
    /**
     * Allows you to set the language codes (two char iso code) that should be enabled for this domain.
     * If you set an empty array as languages this domain will serve all languages
     *
     * @param   array  $languages
     *
     * @return \LaborDigital\T3SAI\Configuration\SearchDomainConfig
     */
    public function setAllowedLanguages(array $languages): SearchDomainConfig
    {
        $this->languages = array_unique($languages);
        
        return $this;
    }
    
    /**
     * Returns the identifier of the TYPO3 site this domain applies to
     * By default this returns: 'default'
     *
     * @return string
     */
    public function getSiteIdentifier(): string
    {
        return $this->siteIdentifier;
    }
    
    /**
     * Allows you to update the identifier of the TYPO3 site this domain applies to
     *
     * @param   string  $siteIdentifier
     *
     * @return SearchDomainConfig
     */
    public function setSiteIdentifier(string $siteIdentifier): SearchDomainConfig
    {
        $this->siteIdentifier = $siteIdentifier;
        
        return $this;
    }
    
    /**
     * Returns the configured siteMap generator options that are set for this domain
     *
     * @return array
     */
    public function getSiteMapOptions(): array
    {
        return $this->siteMapOptions;
    }
    
    /**
     * Can be used to set the options for the site map generator of this domain
     *
     * @param   array  $options  The options for the site map generator
     *                           - addChangeFreq bool (FALSE): if set to true, the changeFreq node will be added to the
     *                           site map elements.
     *                           - addPriority bool (FALSE): if set to true, the priority node will be added to the
     *                           site map elements.
     *
     * @return \LaborDigital\T3SAI\Configuration\SearchDomainConfig
     */
    public function setSiteMapOptions(array $options): SearchDomainConfig
    {
        $this->siteMapOptions = Options::make($options, [
            'addChangeFreq' => false,
            'addPriority'   => false,
        ]);
        
        return $this;
    }
}
