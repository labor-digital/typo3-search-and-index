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
 * Last modified: 2022.04.12 at 20:42
 */

declare(strict_types=1);


namespace LaborDigital\T3sai\Search\Indexer\Page\PageContent;


use RecursiveIterator;

class PageContentIterator implements RecursiveIterator
{
    /**
     * @var array
     */
    protected $list;
    
    /**
     * @var array
     */
    protected $keys;
    
    /**
     * @var int
     */
    protected $keyIndex = 0;
    
    /**
     * @var bool
     */
    protected $rootLevel;
    
    public function __construct(array $list, bool $rootLevel)
    {
        $this->list = $list;
        $this->rootLevel = $rootLevel;
        $this->keys = array_keys($list);
    }
    
    /**
     * @inheritDoc
     */
    public function current()
    {
        if ($this->rootLevel) {
            return $this->list[$this->key()] ?? null;
        }
        
        return $this->list[$this->key()]['record'] ?? null;
    }
    
    /**
     * @inheritDoc
     */
    public function next(): void
    {
        $this->keyIndex++;
    }
    
    /**
     * @inheritDoc
     */
    public function key()
    {
        return $this->keys[$this->keyIndex] ?? null;
    }
    
    /**
     * @inheritDoc
     */
    public function valid(): bool
    {
        return isset($this->keys[$this->keyIndex]);
    }
    
    /**
     * @inheritDoc
     */
    public function rewind(): void
    {
        $this->keyIndex = 0;
    }
    
    /**
     * @inheritDoc
     */
    public function hasChildren(): bool
    {
        return $this->rootLevel || ! empty($this->current()['children']);
    }
    
    /**
     * @inheritDoc
     */
    public function getChildren()
    {
        return new static($this->current(), false);
    }
    
}