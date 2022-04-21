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
use LaborDigital\T3sai\Search\Indexer\Page\PageData\PageDataIterator;

/**
 * Class PageListFilterEvent
 *
 * Dispatched after the page data iterator was created in the page indexer
 * Allows you to modify the list or do something with it yourself.
 *
 * @package LaborDigital\T3sai\Event
 */
class PageListFilterEvent extends AbstractIndexerEvent
{
    
    /**
     * The list of pages that have been gathered for indexing
     *
     * @var PageDataIterator
     */
    protected $pages;
    
    /**
     * The options of the page indexer
     *
     * @var array
     */
    protected $options;
    
    public function __construct(QueueRequest $request, PageDataIterator $pages, array $options)
    {
        parent::__construct($request);
        $this->pages = $pages;
        $this->options = $options;
    }
    
    /**
     * Returns the used configuration for the indexer
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }
    
    /**
     * Returns the list of pages that have been gathered for indexing
     *
     * @return \LaborDigital\T3sai\Search\Indexer\Page\PageData\PageDataIterator
     */
    public function getPages(): PageDataIterator
    {
        return $this->pages;
    }
    
    /**
     * Allows you to update the list of pages that have been gathered for indexing
     *
     * @param   \LaborDigital\T3sai\Search\Indexer\Page\PageData\PageDataIterator  $pages
     *
     * @return PageListFilterEvent
     */
    public function setPages(PageDataIterator $pages): self
    {
        $this->pages = $pages;
        
        return $this;
    }
}
