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
 * Last modified: 2020.07.18 at 13:02
 */

declare(strict_types=1);


namespace LaborDigital\T3SAI\Domain\Entity;


use LaborDigital\T3SAI\Domain\Repository\SearchRepository;
use LaborDigital\T3SAI\Indexer\IndexNode;

class PersistableNodeList
{
    /**
     * The list of all node rows to add to the index
     *
     * @var array
     */
    protected $nodeRows = [];
    
    /**
     * The list of all word rows to add to the index
     *
     * @var array
     */
    protected $wordRows = [];
    
    /**
     * Adds a new node to the this list.
     * Note: Only the persistable data will be added to the list, not the node itself!
     *
     * @param   \LaborDigital\T3SAI\Indexer\IndexNode  $node
     */
    public function addNode(IndexNode $node): void
    {
        $data                             = $node->getPersistedData();
        $this->nodeRows[$node->getGuid()] = $data[SearchRepository::TABLE_NODES];
        $this->wordRows                   = array_merge($this->wordRows, $data[SearchRepository::TABLE_WORDS]);
    }
    
    /**
     * Returns the list of all node rows to add to the index
     *
     * @return array
     */
    public function getNodeRows(): array
    {
        return $this->nodeRows;
    }
    
    /**
     * Returns the list of all word rows to add to the index
     *
     * @return array
     */
    public function getWordRows(): array
    {
        return $this->wordRows;
    }
}
