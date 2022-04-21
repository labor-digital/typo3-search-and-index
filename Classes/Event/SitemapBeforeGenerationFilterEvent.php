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
 * Last modified: 2022.04.20 at 13:46
 */

declare(strict_types=1);


namespace LaborDigital\T3sai\Event;


use Thepixeldeveloper\Sitemap\ChunkedUrlset;

class SitemapBeforeGenerationFilterEvent
{
    /**
     * @var array
     */
    protected $lists;
    
    public function __construct(array $lists)
    {
        $this->lists = $lists;
    }
    
    /**
     * Returns the list of all urls to be persisted by the sitemap generator
     * The list contains nested lists for each host and language
     *
     * @return ChunkedUrlset[][]
     */
    public function getLists(): array
    {
        return $this->lists;
    }
    
    /**
     * Allows you to modify the list of items to be persisted into the sitemap
     *
     * @param   ChunkedUrlset[][]  $lists
     *
     * @return SitemapBeforeGenerationFilterEvent
     */
    public function setLists(array $lists): self
    {
        $this->lists = $lists;
        
        return $this;
    }
}