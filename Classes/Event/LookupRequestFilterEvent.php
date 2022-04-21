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
 * Last modified: 2022.04.19 at 20:51
 */

declare(strict_types=1);


namespace LaborDigital\T3sai\Event;


use LaborDigital\T3sai\Core\Lookup\Request\LookupRequest;

class LookupRequestFilterEvent
{
    /**
     * @var \LaborDigital\T3sai\Core\Lookup\Request\LookupRequest
     */
    protected $request;
    
    public function __construct(LookupRequest $request)
    {
        $this->request = $request;
    }
    
    /**
     * Returns the created request instance to be filtered
     *
     * @return \LaborDigital\T3sai\Core\Lookup\Request\LookupRequest
     */
    public function getRequest(): LookupRequest
    {
        return $this->request;
    }
    
    /**
     * Allows you to replace the request instance before it is returned from the factory
     *
     * @param   \LaborDigital\T3sai\Core\Lookup\Request\LookupRequest  $request
     */
    public function setRequest(LookupRequest $request): void
    {
        $this->request = $request;
    }
}