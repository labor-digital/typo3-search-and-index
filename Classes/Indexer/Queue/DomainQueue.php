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
 * Last modified: 2020.07.17 at 16:50
 */

declare(strict_types=1);


namespace LaborDigital\T3SAI\Indexer\Queue;


use LaborDigital\T3SAI\Domain\Repository\SearchRepository;
use LaborDigital\T3SAI\Event\IndexerPrepareSearchDomainEvent;
use LaborDigital\T3SAI\Exception\Indexer\InvalidIndexerConfigException;
use LaborDigital\T3SAI\Indexer\IndexerContext;
use Throwable;

class DomainQueue
{
    /**
     * The list of all registered domain configurations
     *
     * @var \LaborDigital\T3SAI\Configuration\SearchDomainConfig[]
     */
    protected $domainConfigurations;
    
    /**
     * The search repository for database actions
     *
     * @var \LaborDigital\T3SAI\Domain\Repository\SearchRepository
     */
    protected $searchRepository;
    
    /**
     * The current search indexer context
     *
     * @var \LaborDigital\T3SAI\Indexer\IndexerContext
     */
    protected $context;
    
    /**
     * DomainQueue constructor.
     *
     * @param   array                                                   $domainConfigurations
     * @param   \LaborDigital\T3SAI\Domain\Repository\SearchRepository  $searchRepository
     * @param   \LaborDigital\T3SAI\Indexer\IndexerContext              $context
     */
    public function __construct(
        array $domainConfigurations,
        SearchRepository $searchRepository,
        IndexerContext $context
    ) {
        $this->domainConfigurations = $domainConfigurations;
        $this->searchRepository     = $searchRepository;
        $this->context              = $context;
    }
    
    public function process(): void
    {
        // Die if there is nothing we can process
        if (empty($this->domainConfigurations)) {
            throw new InvalidIndexerConfigException(
                'There are currently no registered search domains! Please register one using the ext config option!'
            );
        }
        
        // Iterate the registered domain configurations
        foreach ($this->domainConfigurations as $domainKey => $domainConfig) {
            try {
                // Set the domain key
                $this->context->setDomainKey($domainKey);
                $this->context->setDomainConfig($domainConfig);
                
                // Find the correct site identifier
                $site = $domainConfig->getSiteIdentifier();
                if ($site === 'default') {
                    $sites = $this->context->getTypoContext()->Site()->getAll();
                    $site  = reset($sites)->getIdentifier();
                }
                
                // Run the simulation for this config
                $this->context->setDomainKey($domainKey);
                $this->context->getEnvironmentSimulator()->runWithEnvironment(
                    ['site' => $site],
                    function () {
                        $this->processSingleDomainConfig();
                    }
                );
            } catch (Throwable $exception) {
                // Catch the error
                $this->context->logError(
                    'Failed to process a domain configuration! ' .
                    'Domain: ' . $domainKey .
                    ', Error:' . $exception->getMessage() .
                    ', File: ' . $exception->getFile() . ' (' . $exception->getLine() . ')',
                    [$exception->getTraceAsString()]
                );
            } finally {
                // Restore the domain key
                $this->context->setDomainKey('LIMBO');
                $this->context->setDomainConfig(null);
            }
        }
    }
    
    /**
     * Called once for every search domain configuration object
     * It instantiates the language and transformer queue based on the current configuration
     * and collects the list of possible nodes with them.
     */
    protected function processSingleDomainConfig(): void
    {
        // Allow filtering
        $this->context->getEventBus()
                      ->dispatch(new IndexerPrepareSearchDomainEvent($this->context));
        
        // Prepare the queue
        $container        = $this->context->getContainer();
        $languageQueue    = $container->getWithoutDi(LanguageQueue::class, [$this->context]);
        $transformerQueue = $container->getWithoutDi(TransformerQueue::class, [$languageQueue, $this->context]);
        
        // Run the queue
        $this->searchRepository->persistNodes(
            $transformerQueue->process()
        );
    }
}
