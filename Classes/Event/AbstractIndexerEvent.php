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
 * Last modified: 2020.07.17 at 20:10
 */

declare(strict_types=1);


namespace LaborDigital\T3SAI\Event;

use LaborDigital\T3SAI\Indexer\IndexerContext;

abstract class AbstractIndexerEvent
{
    /**
     * The indexer context currently running
     *
     * @var \LaborDigital\T3SAI\Indexer\IndexerContext
     */
    protected $context;
    
    /**
     * IndexerBeforeResolveEvent constructor.
     *
     * @param   \LaborDigital\T3SAI\Indexer\IndexerContext  $context
     */
    public function __construct(IndexerContext $context)
    {
        $this->context = $context;
    }
    
    /**
     * Returns the indexer context currently running
     *
     * @return \LaborDigital\T3SAI\Indexer\IndexerContext
     */
    public function getContext(): IndexerContext
    {
        return $this->context;
    }
}
