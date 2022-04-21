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
 * Last modified: 2022.04.19 at 20:53
 */

declare(strict_types=1);


namespace LaborDigital\T3sai\Event;


use LaborDigital\T3sai\Core\Indexer\Queue\QueueRequest;

class IndexerQueueRequestFilterEvent
{
    /**
     * @var \LaborDigital\T3sai\Core\Indexer\Queue\QueueRequest
     */
    protected $request;
    
    public function __construct(QueueRequest $request)
    {
        $this->request = $request;
    }
    
    /**
     * Returns the newly created, mostly empty indexer request to be filtered
     *
     * @return \LaborDigital\T3sai\Core\Indexer\Queue\QueueRequest
     */
    public function getRequest(): QueueRequest
    {
        return $this->request;
    }
    
    /**
     * Allows you to replace the indexer request object before it is returned by the factory
     *
     * @param   \LaborDigital\T3sai\Core\Indexer\Queue\QueueRequest  $request
     */
    public function setRequest(QueueRequest $request): void
    {
        $this->request = $request;
    }
}