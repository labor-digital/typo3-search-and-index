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


use LaborDigital\T3SAI\Configuration\PageDataTransformerConfig;
use LaborDigital\T3SAI\Indexer\IndexNode;

/**
 * Class PageIndexNodeFilterEvent
 *
 * Is dispatched once for every page node that is transformed using the page transformer
 *
 * @package LaborDigital\T3SAI\Event
 */
class PageIndexNodeFilterEvent extends AbstractIndexerEvent
{
    /**
     * Returns the index node that is currently being filled
     *
     * @return \LaborDigital\T3SAI\Indexer\IndexNode
     */
    public function getNode(): IndexNode
    {
        return $this->context->getNode();
    }
    
    /**
     * Returns the database row that is used to fill the node
     *
     * @return array
     */
    public function getPageRecord(): array
    {
        return $this->context->getElement();
    }
    
    /**
     * Returns the used configuration for the transformer
     *
     * @return \LaborDigital\T3SAI\Configuration\PageDataTransformerConfig
     */
    public function getConfig(): PageDataTransformerConfig
    {
        return $this->context->getDomainConfig()->getPageTransformerConfig();
    }
    
    
}
