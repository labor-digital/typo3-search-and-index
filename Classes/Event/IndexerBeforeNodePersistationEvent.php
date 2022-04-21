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
 * Last modified: 2022.04.20 at 12:15
 */

declare(strict_types=1);


namespace LaborDigital\T3sai\Event;


use LaborDigital\T3sai\Core\Indexer\Node\Node;

class IndexerBeforeNodePersistationEvent
{
    /**
     * @var \LaborDigital\T3sai\Core\Indexer\Node\Node
     */
    protected $node;
    
    /**
     * @var array
     */
    protected $nodeRow;
    
    /**
     * @var array
     */
    protected $wordRows;
    
    public function __construct(Node $node, array $nodeRow, array $wordRows)
    {
        $this->node = $node;
        $this->nodeRow = $nodeRow;
        $this->wordRows = $wordRows;
    }
    
    /**
     * Returns the node used to generate the database rows
     *
     * @return \LaborDigital\T3sai\Core\Indexer\Node\Node
     */
    public function getNode(): Node
    {
        return $this->node;
    }
    
    /**
     * Returns the prepared database NODE row, about to be persisted in the database
     *
     * @return array
     */
    public function getNodeRow(): array
    {
        return $this->nodeRow;
    }
    
    /**
     * Allows you to override the prepared database NODE row, about to be persisted in the database
     *
     * @param   array  $nodeRow
     *
     * @return \LaborDigital\T3sai\Event\IndexerBeforeNodePersistationEvent
     */
    public function setNodeRow(array $nodeRow): self
    {
        $this->nodeRow = $nodeRow;
        
        return $this;
    }
    
    /**
     * Returns the list of prepared database WORD rows, about to be persisted in the database
     *
     * @return array
     */
    public function getWordRows(): array
    {
        return $this->wordRows;
    }
    
    /**
     * Allows you to override the prepared database WORD rows, about to be persisted in the database
     *
     * @param   array  $wordRows
     *
     * @return \LaborDigital\T3sai\Event\IndexerBeforeNodePersistationEvent
     */
    public function setWordRows(array $wordRows): self
    {
        $this->wordRows = $wordRows;
        
        return $this;
    }
}