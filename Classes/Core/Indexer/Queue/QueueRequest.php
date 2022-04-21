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
 * Last modified: 2022.04.11 at 14:18
 */

declare(strict_types=1);


namespace LaborDigital\T3sai\Core\Indexer\Queue;


use LaborDigital\T3ba\Core\Di\NoDiInterface;
use LaborDigital\T3sai\Core\Indexer\Node\Node;
use LaborDigital\T3sai\Exception\Indexer\InLimboException;
use LaborDigital\T3sai\Search\Indexer\RecordIndexerInterface;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

class QueueRequest implements NoDiInterface
{
    
    protected $domainConfig = [];
    protected $simulationConfig = [];
    
    /**
     * @var \TYPO3\CMS\Core\Site\Entity\Site|null
     */
    protected $site;
    
    /**
     * @var \TYPO3\CMS\Core\Site\Entity\SiteLanguage|null
     */
    protected $language;
    
    /**
     * @var string|null
     */
    protected $domainIdentifier;
    
    /**
     * @var \LaborDigital\T3sai\Search\Indexer\RecordIndexerInterface|null
     */
    protected $recordIndexer;
    
    /**
     * @var mixed
     */
    protected $element;
    
    /**
     * @var \LaborDigital\T3sai\Core\Indexer\Node\Node
     */
    protected $node;
    
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
     * Updates the configuration array of the search domain this request processes
     *
     * @param   array  $domainConfig
     *
     * @return \LaborDigital\T3sai\Core\Indexer\Queue\QueueRequest
     */
    public function withDomainConfig(array $domainConfig): self
    {
        $clone = clone $this;
        $clone->domainConfig = $domainConfig;
        
        return $clone;
    }
    
    /**
     * Returns the currently set options for the environment simulation
     *
     * @return array
     * @see \LaborDigital\T3ba\Tool\Simulation\EnvironmentSimulator::runWithEnvironment()
     */
    public function getSimulationConfig(): array
    {
        return $this->simulationConfig;
    }
    
    /**
     * Updates the environment simulation configuration to be used in the
     * {@link \LaborDigital\T3sai\Core\Indexer\Queue\Implementation\EnvironmentSimulationHandler}.
     *
     * @param   array  $config  The options to use for the simulation.
     *                          {@see \LaborDigital\T3ba\Tool\Simulation\EnvironmentSimulator::runWithEnvironment}
     *                          for the list of availabe options
     *
     * @return $this
     */
    public function withSimulationConfig(array $config): self
    {
        $clone = clone $this;
        $clone->simulationConfig = $config;
        
        return $clone;
    }
    
    /**
     * Returns the TYPO3 site instance for which this request is being processed
     *
     * @return \TYPO3\CMS\Core\Site\Entity\Site
     */
    public function getSite(): Site
    {
        $this->validateRequiredArg('site');
        
        return $this->site;
    }
    
    /**
     * Updates the TYPO3 site instance this request processes
     *
     * @param   \TYPO3\CMS\Core\Site\Entity\Site  $site
     *
     * @return $this
     */
    public function withSite(Site $site): self
    {
        $clone = clone $this;
        $clone->site = $site;
        
        return $clone;
    }
    
    /**
     * Returns the site language object processed by this request
     *
     * @return \TYPO3\CMS\Core\Site\Entity\SiteLanguage
     */
    public function getLanguage(): SiteLanguage
    {
        $this->validateRequiredArg('language');
        
        return $this->language;
    }
    
    /**
     * Updates the site language of the request
     *
     * @param   \TYPO3\CMS\Core\Site\Entity\SiteLanguage  $language
     *
     * @return $this
     */
    public function withLanguage(SiteLanguage $language): self
    {
        $clone = clone $this;
        $clone->language = $language;
        
        return $clone;
    }
    
    /**
     * Returns the unique identifier of the domain this request processes
     *
     * @return string
     */
    public function getDomainIdentifier(): string
    {
        $this->validateRequiredArg('domainIdentifier');
        
        return $this->domainIdentifier;
    }
    
    /**
     * Updates the unique identifier of the domain the request processes
     *
     * @param   string  $domainIdentifier
     *
     * @return \LaborDigital\T3sai\Core\Indexer\Queue\QueueRequest
     */
    public function withDomainIdentifier(string $domainIdentifier): self
    {
        $clone = clone $this;
        $clone->domainIdentifier = $domainIdentifier;
        
        return $clone;
    }
    
    /**
     * Returns the currently used record indexer instance
     *
     * @return \LaborDigital\T3sai\Search\Indexer\RecordIndexerInterface
     */
    public function getRecordIndexer(): RecordIndexerInterface
    {
        $this->validateRequiredArg('recordIndexer');
        
        return $this->recordIndexer;
    }
    
    /**
     * Updates the currently used record indexer instance with a new one
     *
     * @param   \LaborDigital\T3sai\Search\Indexer\RecordIndexerInterface  $indexer
     *
     * @return $this
     */
    public function withRecordIndexer(RecordIndexerInterface $indexer): self
    {
        $clone = clone $this;
        $clone->recordIndexer = $indexer;
        
        return $clone;
    }
    
    /**
     * Returns the currently indexed element.
     * This is ONE of the resolved, iterable items to be converted into the node
     *
     * @return mixed
     */
    public function getElement()
    {
        return $this->element;
    }
    
    /**
     * Updates the currently indexed element.
     *
     * @param $element
     *
     * @return $this
     */
    public function withElement($element): self
    {
        $clone = clone $this;
        $clone->element = $element;
        
        return $clone;
    }
    
    /**
     * Returns the currently active indexer node to be filled
     *
     * @return \LaborDigital\T3sai\Core\Indexer\Node\Node
     */
    public function getNode(): Node
    {
        $this->validateRequiredArg('node');
        
        return $this->node;
    }
    
    /**
     * Updates the currently active indexer node
     *
     * @param   \LaborDigital\T3sai\Core\Indexer\Node\Node  $node
     *
     * @return $this
     */
    public function withNode(Node $node): self
    {
        $clone = clone $this;
        $clone->node = $node;
        
        return $this;
    }
    
    /**
     * Checks if a given argument has content or throws an exception
     *
     * @param   string  $arg
     *
     * @return void
     * @throws \LaborDigital\T3sai\Exception\Indexer\InLimboException
     */
    protected function validateRequiredArg(string $arg): void
    {
        if (! isset($this->$arg)) {
            throw new InLimboException('The required property: "' . $arg . '" was not yet filled by the indexer');
        }
    }
}