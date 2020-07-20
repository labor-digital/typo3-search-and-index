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
 * Last modified: 2020.07.16 at 18:48
 */

declare(strict_types=1);

namespace LaborDigital\T3SAI\Builtin\Transformer\Page\ContentElement;


use LaborDigital\T3SAI\Indexer\IndexerContext;
use TYPO3\CMS\Core\SingletonInterface;

class ContentElementQueue implements SingletonInterface
{
    /**
     * The list of instantiated content element transformers
     *
     * @var array
     */
    protected $elementTransformers = [];
    
    /**
     * Converts the given row of the tt_content table into a more speaking/useful string we can add to our search index.
     *
     * @param   array                                       $row
     * @param   \LaborDigital\T3SAI\Indexer\IndexerContext  $context
     *
     * @return string
     */
    public function process(array $row, IndexerContext $context): string
    {
        // Try to use one of our registered handlers
        $cType    = ! empty($row['CType']) ? $row['CType'] : '';
        $listType = ! empty($row['list_type']) ? $row['list_type'] : '';
        foreach ($this->getElementTransformers($context) as $elementTransformer) {
            if (! $elementTransformer->canHandle($cType, $listType, $row, $context)) {
                continue;
            }
            
            return $elementTransformer->convert($row, $context);
        }
        
        // Nothing was found...
        return '';
    }
    
    /**
     * Returns the instances of the registered content element transformer
     *
     * @param   \LaborDigital\T3SAI\Indexer\IndexerContext  $context
     *
     * @return \LaborDigital\T3SAI\Builtin\Transformer\Page\ContentElement\ContentElementTransformerInterface[]
     */
    protected function getElementTransformers(IndexerContext $context): array
    {
        if (! empty($this->elementTransformers)) {
            return $this->elementTransformers;
        }
        
        // Register the "given" transformers
        foreach (
            $context->getDomainConfig()->getContentElementTransformers()
            as $transformerClass
        ) {
            $this->elementTransformers[] = $context->getContainer()->get($transformerClass);
        }
        
        // Register fallback handler
        $this->elementTransformers[] = $context->getContainer()->get(DefaultContentElementTransformer::class);
        
        // Done
        return $this->elementTransformers;
    }
}
