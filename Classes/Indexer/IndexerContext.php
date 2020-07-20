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
 * Last modified: 2019.03.22 at 18:56
 */

namespace LaborDigital\T3SAI\Indexer;


use DateTime;
use LaborDigital\T3SAI\Configuration\SearchDomainConfig;
use LaborDigital\T3SAI\Exception\Indexer\InLimboException;
use LaborDigital\T3SAI\Indexer\Transformer\RecordTransformerInterface;
use LaborDigital\Typo3BetterApi\Container\TypoContainer;
use LaborDigital\Typo3BetterApi\Event\TypoEventBus;
use LaborDigital\Typo3BetterApi\Link\LinkService;
use LaborDigital\Typo3BetterApi\Simulation\EnvironmentSimulator;
use LaborDigital\Typo3BetterApi\TypoContext\TypoContext;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

/**
 * Class IndexGeneratorContext
 *
 * @package LaborDigital\T3SAI\Indexer
 */
class IndexerContext implements LoggerAwareInterface
{
    use LoggerAwareTrait;
    
    /**
     * The Link service to generate Links with
     *
     * @var \LaborDigital\Typo3BetterApi\Link\LinkService
     */
    protected $linkService;
    
    /**
     * The instance of the used environment simulator
     *
     * @var \LaborDigital\Typo3BetterApi\Simulation\EnvironmentSimulator
     */
    protected $environmentSimulator;
    
    /**
     * A utility class which helps to parse contents for the index.
     * This is mostly internal, but I will see it as part of the public API.
     *
     * @var \LaborDigital\T3SAI\Indexer\ContentParser
     */
    protected $contentParser;
    
    /**
     * The container instance to resolve new instances with
     *
     * @var \LaborDigital\Typo3BetterApi\Container\TypoContainer
     */
    protected $container;
    
    /**
     * The site's TYPO3 context object
     *
     * @var \LaborDigital\Typo3BetterApi\TypoContext\TypoContext
     */
    protected $typoContext;
    
    /**
     * The language instance that is currently processed
     *
     * @var IndexerSiteLanguage|null
     */
    protected $language;
    
    /**
     * The transformer instance which is currently processed
     *
     * @var RecordTransformerInterface|null
     */
    protected $transformer;
    
    /**
     * The element which gets currently converted
     *
     * @var mixed
     */
    protected $element;
    
    /**
     * The index node to fill with the data based on the given element
     *
     * @var IndexNode
     */
    protected $node;
    
    /**
     * The current date
     *
     * @var \Neunerlei\TinyTimy\DateTimy
     */
    protected $today;
    
    /**
     * The key of the domain we are currently working with
     *
     * @var string
     */
    protected $domainKey = 'LIMBO';
    
    /**
     * The current search domain configuration object
     *
     * @var \LaborDigital\T3SAI\Configuration\SearchDomainConfig|null
     */
    protected $domainConfig;
    
    /**
     * The event bus instance
     *
     * @var \LaborDigital\Typo3BetterApi\Event\TypoEventBus
     */
    protected $eventBus;
    
    /**
     * A list of errors that have popped up while we were running the indexer
     *
     * @var array
     */
    protected $errors = [];
    
    /**
     * IndexerContext constructor.
     *
     * @param   \LaborDigital\Typo3BetterApi\Link\LinkService                 $linkService
     * @param   \LaborDigital\Typo3BetterApi\Simulation\EnvironmentSimulator  $environmentSimulator
     * @param   \LaborDigital\T3SAI\Indexer\ContentParser                     $contentParser
     * @param   \LaborDigital\Typo3BetterApi\Event\TypoEventBus               $eventBus
     * @param   \LaborDigital\Typo3BetterApi\Container\TypoContainer          $container
     * @param   \LaborDigital\Typo3BetterApi\TypoContext\TypoContext          $typoContext
     */
    public function __construct(
        LinkService $linkService,
        EnvironmentSimulator $environmentSimulator,
        ContentParser $contentParser,
        TypoEventBus $eventBus,
        TypoContainer $container,
        TypoContext $typoContext
    ) {
        $this->linkService          = $linkService;
        $this->environmentSimulator = $environmentSimulator;
        $this->contentParser        = $contentParser;
        $this->eventBus             = $eventBus;
        $this->container            = $container;
        $this->typoContext          = $typoContext;
    }
    
    /**
     * Returns the active language which is currently indexed
     *
     * @return IndexerSiteLanguage
     * @throws \LaborDigital\T3SAI\Exception\Indexer\InLimboException
     */
    public function getLanguage(): IndexerSiteLanguage
    {
        if ($this->language === null) {
            throw new InLimboException('IN LIMBO: There is currently no language set!');
        }
        
        return $this->language;
    }
    
    /**
     * Sets the language which is currently indexed
     *
     * @param   IndexerSiteLanguage  $language
     */
    public function setLanguage(?IndexerSiteLanguage $language): void
    {
        $this->language = $language;
    }
    
    /**
     * Returns the currently running data transformer
     *
     * @return RecordTransformerInterface
     * @throws \LaborDigital\T3SAI\Exception\Indexer\InLimboException
     */
    public function getTransformer(): RecordTransformerInterface
    {
        if ($this->language === null) {
            throw new InLimboException('IN LIMBO: There is currently no transformer set!');
        }
        
        return $this->transformer;
    }
    
    /**
     * Sets the currently active data transformer
     *
     * @param   RecordTransformerInterface  $transformer
     */
    public function setTransformer(?RecordTransformerInterface $transformer): void
    {
        $this->transformer = $transformer;
    }
    
    /**
     * Returns the current element
     *
     * @return mixed
     */
    public function getElement()
    {
        return $this->element;
    }
    
    /**
     * Sets the current element
     *
     * @param   mixed  $element
     */
    public function setElement($element): void
    {
        $this->element = $element;
    }
    
    /**
     * Returns the search domain configuration object
     *
     * @return \LaborDigital\T3SAI\Configuration\SearchDomainConfig
     * @throws \LaborDigital\T3SAI\Exception\Indexer\InLimboException
     */
    public function getDomainConfig(): SearchDomainConfig
    {
        if ($this->domainConfig === null) {
            throw new InLimboException('IN LIMBO: There is currently no search domain config set!');
        }
        
        return $this->domainConfig;
    }
    
    /**
     * Sets the search domain configuration object
     *
     * @param   \LaborDigital\T3SAI\Configuration\SearchDomainConfig|null  $domainConfig
     *
     * @return IndexerContext
     */
    public function setDomainConfig(?SearchDomainConfig $domainConfig): IndexerContext
    {
        $this->domainConfig = $domainConfig;
        
        return $this;
    }
    
    /**
     * Returns the current node for the current $element
     *
     * @return IndexNode
     * @throws \LaborDigital\T3SAI\Exception\Indexer\InLimboException
     */
    public function getNode(): IndexNode
    {
        if ($this->language === null) {
            throw new InLimboException('IN LIMBO: There is currently no node set!');
        }
        
        return $this->node;
    }
    
    /**
     * Sets the node for the current element
     *
     * @param   IndexNode  $node
     */
    public function setNode(?IndexNode $node): void
    {
        $this->node = $node;
    }
    
    /**
     * Returns the current date object
     *
     * @return \DateTime
     */
    public function getToday(): DateTime
    {
        if (isset($this->today)) {
            return $this->today;
        }
        
        return $this->today = new DateTime();
    }
    
    /**
     * Returns the domain key the index generator is currently working with
     *
     * @return string
     */
    public function getDomainKey(): string
    {
        return $this->domainKey;
    }
    
    /**
     * Sets the domain key the index generator is currently working with
     *
     * @param   string  $domainKey
     *
     * @return IndexerContext
     */
    public function setDomainKey(string $domainKey): IndexerContext
    {
        $this->domainKey = $domainKey;
        
        return $this;
    }
    
    /**
     * Returns the link service instance
     *
     * @return \LaborDigital\Typo3BetterApi\Link\LinkService
     */
    public function getLinkService(): LinkService
    {
        return $this->linkService;
    }
    
    /**
     * Returns the instance of the used environment simulator
     *
     * @return \LaborDigital\Typo3BetterApi\Simulation\EnvironmentSimulator
     */
    public function getEnvironmentSimulator(): EnvironmentSimulator
    {
        return $this->environmentSimulator;
    }
    
    /**
     * Returns a utility class which helps to parse contents
     *
     * @return \LaborDigital\T3SAI\Indexer\ContentParser
     */
    public function getContentParser(): ContentParser
    {
        return $this->contentParser;
    }
    
    /**
     * Returns the event bus instance
     *
     * @return \LaborDigital\Typo3BetterApi\Event\TypoEventBus
     */
    public function getEventBus(): TypoEventBus
    {
        return $this->eventBus;
    }
    
    /**
     * Returns the logger for the indexer
     *
     * @return \Psr\Log\LoggerInterface
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }
    
    /**
     * Returns the DI container instance
     *
     * @return \LaborDigital\Typo3BetterApi\Container\TypoContainer
     */
    public function getContainer(): TypoContainer
    {
        return $this->container;
    }
    
    /**
     * Returns the TYPO3 context instance
     *
     * @return \LaborDigital\Typo3BetterApi\TypoContext\TypoContext
     */
    public function getTypoContext(): TypoContext
    {
        return $this->typoContext;
    }
    
    /**
     * Logs an error both for the indexer and the logger at the same time
     *
     * @param   string  $message
     * @param   array   $context
     *
     * @return $this
     */
    public function logError(string $message, array $context): self
    {
        $this->logger->error($message, $context);
        $this->errors[] = $message;
        
        return $this;
    }
    
    /**
     * Returns a list of errors that have occurred while we ran the indexer
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
