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
 * Last modified: 2022.04.11 at 15:39
 */

declare(strict_types=1);


namespace LaborDigital\T3sai\Core\Indexer\Queue\Implementation;


use LaborDigital\T3sai\Core\Indexer\Node\NodeFactory;
use LaborDigital\T3sai\Core\Indexer\Node\NodePersistenceProvider;
use LaborDigital\T3sai\Core\Indexer\Queue\PostProcessingQueueHandlerInterface;
use LaborDigital\T3sai\Core\Indexer\Queue\QueueHandlerInterface;
use LaborDigital\T3sai\Core\Indexer\Queue\QueueRequest;
use LaborDigital\T3sai\Event\IndexerBeforeConversionEvent;
use LaborDigital\T3sai\Event\IndexerBeforeResolveEvent;
use LaborDigital\T3sai\Event\IndexerNodeFilterEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Throwable;

class NodeHandler implements QueueHandlerInterface, LoggerAwareInterface, PostProcessingQueueHandlerInterface
{
    use LoggerAwareTrait;
    
    /**
     * @var \LaborDigital\T3sai\Core\Indexer\Node\NodeFactory
     */
    protected $nodeFactory;
    
    /**
     * @var \LaborDigital\T3sai\Core\Indexer\Node\NodePersistenceProvider
     */
    protected $nodePersistenceProvider;
    
    /**
     * @var \Psr\EventDispatcher\EventDispatcherInterface
     */
    protected $eventDispatcher;
    
    public function __construct(
        NodeFactory $nodeFactory,
        NodePersistenceProvider $nodePersistenceProvider,
        EventDispatcherInterface $eventDispatcher
    )
    {
        $this->nodeFactory = $nodeFactory;
        $this->nodePersistenceProvider = $nodePersistenceProvider;
        $this->eventDispatcher = $eventDispatcher;
    }
    
    /**
     * @inheritDoc
     */
    public function handle($item, QueueRequest $request, callable $children): void
    {
        $node = $this->nodeFactory->makeNode($request);
        
        $request = $request
            ->withElement($item)
            ->withNode($node);
        
        try {
            $request->getRecordIndexer()->index($item, $node, $request);
            
            $this->eventDispatcher->dispatch(new IndexerNodeFilterEvent($request));
            
            if (! $node->addToSearchResults()) {
                if ($this->logger) {
                    $this->logger->info('Node: ' . $node->getGuid() . ' was excluded from the list of results', ['request' => $request]);
                }
                
                return;
            }
            
            $this->nodePersistenceProvider->attach($node);
        } catch (Throwable $e) {
            if ($this->logger) {
                $this->logger->critical(
                    $e->getMessage(),
                    ['exception' => $e, 'request' => $request]
                );
            }
        }
    }
    
    /**
     * @inheritDoc
     */
    public function provideIterable(QueueRequest $request): iterable
    {
        $this->eventDispatcher->dispatch(new IndexerBeforeResolveEvent($request));
        
        $elements = $request->getRecordIndexer()->resolve($request);
        
        return $this->eventDispatcher->dispatch(
            new IndexerBeforeConversionEvent($request, $elements)
        )->getElements();
    }
    
    /**
     * @inheritDoc
     */
    public function postProcess(QueueRequest $request): void
    {
        $this->nodePersistenceProvider->persistAll();
    }
}