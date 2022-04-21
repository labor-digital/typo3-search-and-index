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


namespace LaborDigital\T3sai\Event;


use LaborDigital\T3sai\Core\Indexer\Queue\QueueRequest;
use LaborDigital\T3sai\Search\Indexer\RecordIndexerInterface;

class IndexerBeforeConversionEvent extends AbstractIndexerEvent
{
    /**
     * The list of elements to process
     *
     * @var iterable
     */
    protected $elements;
    
    public function __construct(QueueRequest $request, iterable $elements)
    {
        parent::__construct($request);
        $this->elements = $elements;
    }
    
    /**
     * Returns the indexer instance that should now start to convert the elements
     *
     * @return \LaborDigital\T3sai\Search\Indexer\RecordIndexerInterface
     */
    public function getIndexer(): RecordIndexerInterface
    {
        return $this->request->getRecordIndexer();
    }
    
    /**
     * Returns the list of all elements that should be converted
     *
     * @return iterable
     */
    public function getElements(): iterable
    {
        return $this->elements;
    }
    
    /**
     * Updates the list of all elements that should be converted
     *
     * @param   iterable  $elements
     *
     * @return \LaborDigital\T3sai\Event\IndexerBeforeConversionEvent
     */
    public function setElements(iterable $elements): IndexerBeforeConversionEvent
    {
        $this->elements = $elements;
        
        return $this;
    }
}
