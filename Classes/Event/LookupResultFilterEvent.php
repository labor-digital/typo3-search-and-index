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
 * Last modified: 2020.07.18 at 20:02
 */

declare(strict_types=1);


namespace LaborDigital\T3sai\Event;


use LaborDigital\T3sai\Core\Lookup\Request\LookupRequest;

class LookupResultFilterEvent
{
    /**
     * @var \LaborDigital\T3sai\Core\Lookup\Request\LookupRequest
     */
    protected $request;
    
    /**
     * The list of processed results
     *
     * @var array
     */
    protected $result;
    
    public function __construct(LookupRequest $request, array $results)
    {
        $this->request = $request;
        $this->result = $results;
    }
    
    /**
     * Returns the type of request for which the result should be filtered
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->request->getType();
    }
    
    /**
     * Returns the request object, used to create the provided list of results
     *
     * @return \LaborDigital\T3sai\Core\Lookup\Request\LookupRequest
     */
    public function getRequest(): LookupRequest
    {
        return $this->request;
    }
    
    /**
     * Returns the processed result
     *
     * @return array
     */
    public function getResult(): array
    {
        return $this->result;
    }
    
    /**
     * Updates the processed result
     *
     * @param   array  $result
     *
     * @return \LaborDigital\T3sai\Event\LookupResultFilterEvent
     */
    public function setResult(array $result): self
    {
        $this->result = $result;
        
        return $this;
    }
}

