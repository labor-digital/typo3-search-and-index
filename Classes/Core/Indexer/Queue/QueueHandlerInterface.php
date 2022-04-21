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
 * Last modified: 2022.04.11 at 14:19
 */

declare(strict_types=1);


namespace LaborDigital\T3sai\Core\Indexer\Queue;


interface QueueHandlerInterface
{
    /**
     * Handles a single request item and calls $children() (with a potentially modified request)
     * in order to run deeper into the layers of handlers
     *
     * @param                                                        $item
     * @param   \LaborDigital\T3sai\Core\Indexer\Queue\QueueRequest  $request
     * @param   callable                                             $children
     *
     * @return void
     */
    public function handle($item, QueueRequest $request, callable $children): void;
    
    /**
     * Returns the list of all iterable items provided by this handler.
     * Each item will result in an additional "handle()" call of the handler
     *
     * @param   \LaborDigital\T3sai\Core\Indexer\Queue\QueueRequest  $request
     *
     * @return iterable
     */
    public function provideIterable(QueueRequest $request): iterable;
}