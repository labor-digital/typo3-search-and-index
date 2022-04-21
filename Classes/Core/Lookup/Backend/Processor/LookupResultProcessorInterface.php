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
 * Last modified: 2022.04.20 at 16:38
 */

declare(strict_types=1);


namespace LaborDigital\T3sai\Core\Lookup\Backend\Processor;


use LaborDigital\T3sai\Core\Lookup\Request\LookupRequest;

interface LookupResultProcessorInterface
{
    /**
     * Must check if the processor can handle the results provided by the given request object
     *
     * @param   \LaborDigital\T3sai\Core\Lookup\Request\LookupRequest  $request
     *
     * @return bool
     */
    public function canHandle(LookupRequest $request): bool;
    
    /**
     * Receives the list of rows to be processed and the initial request.
     * Can do dome kind of post-processing and MUST return the rows as an ARRAY
     *
     * @param   iterable                                               $rows
     * @param   \LaborDigital\T3sai\Core\Lookup\Request\LookupRequest  $request
     *
     * @return array
     */
    public function process(iterable $rows, LookupRequest $request): array;
}