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


use LaborDigital\T3SAI\Indexer\IndexerContext;
use LaborDigital\T3SAI\Indexer\Transformer\RecordTransformerInterface;

class IndexerBeforeConversionEvent extends AbstractIndexerEvent
{
    /**
     * The list of elements to process
     *
     * @var iterable
     */
    protected $elements;
    
    /**
     * @inheritDoc
     */
    public function __construct(IndexerContext $context, iterable $elements)
    {
        parent::__construct($context);
        $this->elements = $elements;
    }
    
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
     * @return \LaborDigital\T3SAI\Event\IndexerBeforeConversionEvent
     */
    public function setElements(iterable $elements): IndexerBeforeConversionEvent
    {
        $this->elements = $elements;
        
        return $this;
    }
}
