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
use LaborDigital\T3SAI\Indexer\IndexerContext;

/**
 * Class PageListFilterEvent
 *
 * Dispatched after all pages have been gathered by the page transformer.
 * Used to apply additional filtering or post processing of the page list
 *
 * @package LaborDigital\T3SAI\Event
 */
class PageListFilterEvent extends AbstractIndexerEvent
{
    
    /**
     * The list of pages that have been gathered for indexing
     *
     * @var array
     */
    protected $pages;
    
    /**
     * @inheritDoc
     */
    public function __construct(IndexerContext $context, array $pages)
    {
        parent::__construct($context);
        $this->pages = $pages;
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
    
    /**
     * Returns the list of pages that have been gathered for indexing
     *
     * @return array
     */
    public function getPages(): array
    {
        return $this->pages;
    }
    
    /**
     * Allows you to update the list of pages that have been gathered for indexing
     *
     * @param   array  $pages
     *
     * @return PageListFilterEvent
     */
    public function setPages(array $pages): PageListFilterEvent
    {
        $this->pages = $pages;
        
        return $this;
    }
}
