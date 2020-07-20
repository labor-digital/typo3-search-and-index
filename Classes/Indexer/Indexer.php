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
 * Last modified: 2019.03.22 at 17:15
 */

namespace LaborDigital\T3SAI\Indexer;

use LaborDigital\T3SAI\Configuration\SearchConfigRepository;
use LaborDigital\T3SAI\Domain\Repository\SearchRepository;
use LaborDigital\T3SAI\Event\IndexerAfterActivateEvent;
use LaborDigital\T3SAI\Event\IndexerBeforeActivateEvent;
use LaborDigital\T3SAI\Exception\Indexer\InLimboException;
use LaborDigital\T3SAI\Indexer\Queue\DomainQueue;
use LaborDigital\Typo3BetterApi\Container\TypoContainerInterface;

class Indexer
{
    
    /**
     * @var \LaborDigital\T3SAI\Indexer\IndexerContext|null
     */
    protected $context;
    
    /**
     * @var \LaborDigital\Typo3BetterApi\Container\TypoContainerInterface
     */
    protected $container;
    
    /**
     * @var \LaborDigital\T3SAI\Configuration\SearchConfigRepository
     */
    protected $configRepository;
    
    /**
     * @var \LaborDigital\T3SAI\Domain\Repository\SearchRepository
     */
    protected $searchRepository;
    
    
    /**
     * Indexer constructor.
     *
     * @param   \LaborDigital\Typo3BetterApi\Container\TypoContainerInterface  $container
     * @param   \LaborDigital\T3SAI\Configuration\SearchConfigRepository       $configRepository
     * @param   \LaborDigital\T3SAI\Domain\Repository\SearchRepository         $searchRepository
     */
    public function __construct(
        TypoContainerInterface $container,
        SearchConfigRepository $configRepository,
        SearchRepository $searchRepository
    ) {
        $this->container        = $container;
        $this->searchRepository = $searchRepository;
        $this->configRepository = $configRepository;
    }
    
    
    /**
     * Entry point to start the search indexing process
     *
     * @throws \LaborDigital\T3SAI\Exception\SearchAndIndexException
     */
    public function run(): void
    {
        // Create the context
        $this->context = $this->container->get(IndexerContext::class);
        
        // Clean up
        $this->searchRepository->removeInactiveNodes();
        
        // Run the domain queue
        $this->container->getWithoutDi(DomainQueue::class, [
            $this->configRepository->getDomainConfigurations(),
            $this->searchRepository,
            $this->context,
        ])->process();
        
        // Activate the new nodes
        $this->context->getEventBus()->dispatch(new IndexerBeforeActivateEvent($this->context));
        $this->searchRepository->activateNewNodesAndRemoveOldOnes();
        $this->context->getEventBus()->dispatch(new IndexerAfterActivateEvent($this->context));
    }
    
    /**
     * Returns true if there were errors while running the generator, false if not
     *
     * @return bool
     */
    public function hasErrors(): bool
    {
        return ! empty($this->getErrors());
    }
    
    /**
     * Returns the list of errors that occurred while running the generator
     *
     * @return array
     * @throws \LaborDigital\T3SAI\Exception\Indexer\InLimboException
     */
    public function getErrors(): array
    {
        if (! isset($this->context)) {
            throw new InLimboException('You can only call this method after you executed the run() method first!');
        }
        
        return $this->context->getErrors();
    }
}
