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
 * Last modified: 2022.04.12 at 17:44
 */

declare(strict_types=1);


namespace LaborDigital\T3sai\Search\Indexer\Page\PageData;


use Iterator;
use LaborDigital\T3sai\Exception\Indexer\IndexerException;

class PageDataIterator implements Iterator
{
    /**
     * @var array
     */
    protected $rootPids;
    
    /**
     * @var \LaborDigital\T3sai\Search\Indexer\Page\PageData\PageDataResolver
     */
    protected $dataResolver;
    
    protected $fallbackCounter = 0;
    protected $visitedPids = [];
    protected $pids;
    protected $chunk = [];
    
    /**
     * @var mixed
     */
    protected $current;
    
    public function __construct(array $rootPids, PageDataResolver $dataResolver)
    {
        $this->rootPids = $rootPids;
        $this->pids = $rootPids;
        $this->dataResolver = $dataResolver;
    }
    
    /**
     * @inheritDoc
     */
    public function current()
    {
        return $this->current;
    }
    
    /**
     * @inheritDoc
     */
    public function next(): void
    {
        $this->current = null;
        
        if (empty($this->chunk)) {
            $this->findChunk();
        }
        
        if (empty($this->chunk)) {
            return;
        }
        
        $this->current = array_shift($this->chunk);
    }
    
    /**
     * @inheritDoc
     */
    public function key()
    {
        return $this->current['uid'] ?? 0;
    }
    
    /**
     * @inheritDoc
     */
    public function valid(): bool
    {
        if ($this->current === null && ! empty($this->pids)) {
            $this->next();
        }
        
        return $this->current !== null;
    }
    
    /**
     * @inheritDoc
     */
    public function rewind(): void
    {
        $this->pids = $this->rootPids;
        $this->visitedPids = [];
        $this->fallbackCounter = 0;
        $this->chunk = null;
        $this->current = null;
    }
    
    /**
     * Fetches the chunk of menu items that should be iterated next
     *
     * @return void
     * @throws \LaborDigital\T3sai\Exception\Indexer\IndexerException
     */
    protected function findChunk(): void
    {
        if ($this->fallbackCounter++ > 10000) {
            throw new IndexerException('More than 10000 iterations required to find the page tree, this can\'t be right');
        }
        
        // Fetch the root page data (this is only done once, when the iterator loops for the first time)
        if ($this->chunk === null && empty($this->visitedPids)) {
            $this->chunk = $this->dataResolver->resolveInitialChunk($this->rootPids, $this->visitedPids);
            
            return;
        }
        
        if (empty($this->pids)) {
            return;
        }
        
        // This is handles all child pages of our root page
        $this->chunk = $this->dataResolver->resolveSubsequentChunk($this->pids, $this->visitedPids);
        
        if (empty($this->chunk) && ! empty($this->pids)) {
            $this->findChunk();
        }
    }
}