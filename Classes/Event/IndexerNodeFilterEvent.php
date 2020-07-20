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


namespace LaborDigital\T3SAI\Event;


use LaborDigital\T3SAI\Indexer\IndexNode;
use LaborDigital\T3SAI\Indexer\Transformer\RecordTransformerInterface;

/**
 * Class IndexerNodeFilterEvent
 *
 * Dispatched once for every node that was generated in the indexer.
 * Allows you to provide additional post processing after the transformer is finished
 *
 * @package LaborDigital\T3SAI\Event
 */
class IndexerNodeFilterEvent extends AbstractIndexerEvent
{
    /**
     * Returns the indexer instance that should now start to convert the elements
     *
     * @return \LaborDigital\T3SAI\Indexer\Transformer\RecordTransformerInterface
     */
    public function getTransformer(): RecordTransformerInterface
    {
        return $this->context->getTransformer();
    }
    
    /**
     * Returns the index node to filter
     *
     * @return \LaborDigital\T3SAI\Indexer\IndexNode
     */
    public function getNode(): IndexNode
    {
        return $this->context->getNode();
    }
    
    /**
     * Returns the raw data/record for the node to filter
     *
     * @return mixed
     */
    public function getElement()
    {
        return $this->context->getElement();
    }
}
