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
 * Last modified: 2022.04.11 at 19:48
 */

declare(strict_types=1);


namespace LaborDigital\T3sai\Core\Indexer\Node;


use LaborDigital\T3sai\Domain\Repository\IndexerRepository;
use LaborDigital\T3sai\Domain\Repository\SearchRepository;
use LaborDigital\T3sai\Event\IndexerBeforeNodePersistationEvent;
use Psr\EventDispatcher\EventDispatcherInterface;

class NodePersistenceProvider
{
    protected $nodes = [];
    
    /**
     * @var \LaborDigital\T3sai\Core\Indexer\Node\NodeConverter
     */
    protected $converter;
    
    /**
     * @var \LaborDigital\T3sai\Domain\Repository\IndexerRepository
     */
    protected $repository;
    
    /**
     * @var \Psr\EventDispatcher\EventDispatcherInterface
     */
    protected $eventDispatcher;
    
    public function __construct(
        NodeConverter $converter,
        IndexerRepository $repository,
        EventDispatcherInterface $eventDispatcher
    )
    {
        $this->converter = $converter;
        $this->repository = $repository;
        $this->eventDispatcher = $eventDispatcher;
    }
    
    /**
     * Adds a new to the list of persist able nodes.
     * If more than 10 nodes wait for persistation they are automatically send into the database.
     *
     * @param   \LaborDigital\T3sai\Core\Indexer\Node\Node  $node
     *
     * @return void
     */
    public function attach(Node $node): void
    {
        $this->nodes[] = $node;
        
        if (count($this->nodes) >= 10) {
            $this->persistAll();
        }
    }
    
    /**
     * Persists all nodes currently in the queue into the database
     *
     * @return void
     */
    public function persistAll(): void
    {
        $nodeRows = [];
        $wordRows = [];
        
        foreach ($this->nodes as $node) {
            [$nodeRow, $nodeWordRows] = $this->converter->convertToArray($node);
            
            $e = $this->eventDispatcher->dispatch(
                new IndexerBeforeNodePersistationEvent($node, $nodeRow, $nodeWordRows)
            );
            
            $nodeRows[] = $e->getNodeRow();
            $wordRows[] = $e->getWordRows();
        }
        unset($nodeRow, $nodeWordRows);
        
        $wordRows = array_merge(...$wordRows);
        
        $this->repository->persistRows(SearchRepository::TABLE_NODES, $nodeRows);
        $this->repository->persistRows(SearchRepository::TABLE_WORDS, $wordRows);
        
        $this->nodes = [];
    }
}