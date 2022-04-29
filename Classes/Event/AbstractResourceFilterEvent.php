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
 * Last modified: 2022.04.29 at 14:54
 */

declare(strict_types=1);


namespace LaborDigital\T3sai\Event;


use LaborDigital\T3fa\Core\Resource\Query\ResourceQuery;
use LaborDigital\T3fa\Core\Resource\Repository\Context\ResourceCollectionContext;

abstract class AbstractResourceFilterEvent
{
    /**
     * @var \LaborDigital\T3fa\Core\Resource\Query\ResourceQuery
     */
    protected $resourceQuery;
    
    /**
     * @var \LaborDigital\T3fa\Core\Resource\Repository\Context\ResourceCollectionContext
     */
    protected $context;
    
    public function __construct(ResourceQuery $resourceQuery, ResourceCollectionContext $context)
    {
        $this->resourceQuery = $resourceQuery;
        $this->context = $context;
    }
    
    /**
     * Returns the T3FA resource query, used to resolve the results
     *
     * @return \LaborDigital\T3fa\Core\Resource\Query\ResourceQuery
     */
    public function getResourceQuery(): ResourceQuery
    {
        return $this->resourceQuery;
    }
    
    /**
     * Returns the T3FA collection context, used to require the autocomplete results
     *
     * @return \LaborDigital\T3fa\Core\Resource\Repository\Context\ResourceCollectionContext
     */
    public function getContext(): ResourceCollectionContext
    {
        return $this->context;
    }
}