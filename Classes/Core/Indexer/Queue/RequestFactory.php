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
 * Last modified: 2022.04.11 at 17:24
 */

declare(strict_types=1);


namespace LaborDigital\T3sai\Core\Indexer\Queue;


use LaborDigital\T3ba\Core\Di\ContainerAwareTrait;
use LaborDigital\T3sai\Core\Indexer\Node\NodeFactory;
use LaborDigital\T3sai\Domain\Repository\DomainConfigRepository;
use LaborDigital\T3sai\Event\IndexerQueueRequestFilterEvent;
use Psr\EventDispatcher\EventDispatcherInterface;

class RequestFactory
{
    use ContainerAwareTrait;
    
    /**
     * @var \LaborDigital\T3sai\Core\Indexer\Node\NodeFactory
     */
    protected $nodeFactory;
    
    /**
     * @var \LaborDigital\T3sai\Domain\Repository\DomainConfigRepository
     */
    protected $configRepository;
    
    /**
     * @var \Psr\EventDispatcher\EventDispatcherInterface
     */
    protected $eventDispatcher;
    
    public function __construct(
        NodeFactory $nodeFactory,
        DomainConfigRepository $configRepository,
        EventDispatcherInterface $eventDispatcher
    )
    {
        $this->nodeFactory = $nodeFactory;
        $this->configRepository = $configRepository;
        $this->eventDispatcher = $eventDispatcher;
    }
    
    /**
     * Creates a new QueueRequest instance, with the domain configuration preloaded
     *
     * @return \LaborDigital\T3sai\Core\Indexer\Queue\QueueRequest
     */
    public function makeRequest(): QueueRequest
    {
        $request = $this->makeInstance(QueueRequest::class, [$this->nodeFactory])
                        ->withDomainConfig($this->configRepository->findAllDomainConfigs());
        
        return $this->eventDispatcher->dispatch(new IndexerQueueRequestFilterEvent($request))->getRequest();
    }
}