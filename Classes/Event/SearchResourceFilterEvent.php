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
 * Last modified: 2022.04.29 at 14:53
 */

declare(strict_types=1);


namespace LaborDigital\T3sai\Event;


use LaborDigital\T3fa\Core\Resource\Query\ResourceQuery;
use LaborDigital\T3fa\Core\Resource\Repository\Context\ResourceCollectionContext;

class SearchResourceFilterEvent extends AbstractResourceFilterEvent
{
    protected $items;
    
    public function __construct(array $items, ResourceQuery $resourceQuery, ResourceCollectionContext $context)
    {
        parent::__construct($resourceQuery, $context);
        $this->items = $items;
    }
    
    /**
     * Returns the list of items that were resolved for the search resource
     *
     * @return array
     */
    public function getItems(): array
    {
        return $this->items;
    }
    
    /**
     * Allows the outside world to override the resolved list of search results
     *
     * @param   array  $items
     *
     * @return $this
     */
    public function setItems(array $items): self
    {
        $this->items = $items;
        
        return $this;
    }
}