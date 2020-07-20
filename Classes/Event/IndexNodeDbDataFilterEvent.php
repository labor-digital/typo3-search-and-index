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
 * Last modified: 2020.07.16 at 17:14
 */

declare(strict_types=1);


namespace LaborDigital\T3SAI\Event;


use LaborDigital\T3SAI\Indexer\IndexerContext;
use LaborDigital\T3SAI\Indexer\IndexNode;

/**
 * Class AddNodeDbRowFilterEvent
 *
 * Can be used to filter the DB array of a single row, before it is stored in the database
 *
 * @package LaborDigital\T3SAI\Event
 */
class IndexNodeDbDataFilterEvent extends AbstractIndexerEvent
{
    /**
     * The prepared node row to save in the database
     *
     * @var array
     */
    protected $nodeRow;
    
    /**
     * The list of all rows to add to the word table
     *
     * @var array
     */
    protected $wordRows;
    
    /**
     * @inheritDoc
     */
    public function __construct(IndexerContext $context, array $nodeRow, array $wordRows)
    {
        parent::__construct($context);
        $this->nodeRow  = $nodeRow;
        $this->wordRows = $wordRows;
    }
    
    /**
     * Returns the node to persist in the database
     *
     * @return \LaborDigital\T3SAI\Indexer\IndexNode
     */
    public function getNode(): IndexNode
    {
        return $this->context->getNode();
    }
    
    /**
     * Returns the prepared node row to save in the database
     *
     * @return array
     */
    public function getNodeRow(): array
    {
        return $this->nodeRow;
    }
    
    /**
     * Allows you to update the prepared node row to save in the database
     *
     * @param   array  $nodeRow
     *
     * @return IndexNodeDbDataFilterEvent
     */
    public function setNodeRow(array $nodeRow): IndexNodeDbDataFilterEvent
    {
        $this->nodeRow = $nodeRow;
        
        return $this;
    }
    
    /**
     * Returns the list of all rows to add to the word table
     *
     * @return array
     */
    public function getWordRows(): array
    {
        return $this->wordRows;
    }
    
    /**
     * Updates the list of all rows to add to the word table
     *
     * @param   array  $wordRows
     *
     * @return IndexNodeDbDataFilterEvent
     */
    public function setWordRows(array $wordRows): IndexNodeDbDataFilterEvent
    {
        $this->wordRows = $wordRows;
        
        return $this;
    }
}
