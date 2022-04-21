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

namespace LaborDigital\T3sai\Core\Indexer;

use LaborDigital\T3sai\Core\Indexer\Queue\RequestFactory;
use LaborDigital\T3sai\Core\Indexer\Queue\Runner;
use LaborDigital\T3sai\Domain\Repository\IndexerRepository;
use LaborDigital\T3sai\Event\IndexerAfterActivateEvent;
use LaborDigital\T3sai\Event\IndexerBeforeActivateEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\SingletonInterface;

class Indexer implements SingletonInterface
{
    /**
     * @var \LaborDigital\T3sai\Core\Indexer\Queue\Runner
     */
    protected $runner;
    
    /**
     * @var \LaborDigital\T3sai\Domain\Repository\IndexerRepository
     */
    protected $repository;
    
    /**
     * @var \LaborDigital\T3sai\Core\Indexer\Queue\RequestFactory
     */
    protected $requestFactory;
    
    /**
     * @var \Psr\EventDispatcher\EventDispatcherInterface
     */
    protected $eventDispatcher;
    
    public function __construct(
        Runner $runner,
        IndexerRepository $searchRepository,
        RequestFactory $requestFactory,
        EventDispatcherInterface $eventDispatcher
    )
    {
        $this->runner = $runner;
        $this->repository = $searchRepository;
        $this->requestFactory = $requestFactory;
        $this->eventDispatcher = $eventDispatcher;
    }
    
    /**
     * Starts the indexing by dispatching the indexer request through the runner implementation.
     * Returns either null -> everything is alright, or an array containing errors that occurred while running.
     *
     * @throws \LaborDigital\T3sai\Exception\SearchAndIndexException
     */
    public function run(): ?array
    {
        $this->repository->removeInactiveNodes();
        $request = $this->requestFactory->makeRequest();
        
        $result = $this->runner->run($request);
        
        $this->eventDispatcher->dispatch(new IndexerBeforeActivateEvent());
        $this->repository->activateNewNodesAndRemoveOldOnes();
        $this->eventDispatcher->dispatch(new IndexerAfterActivateEvent());
        
        return $result;
    }
}
