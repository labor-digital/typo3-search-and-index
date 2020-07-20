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
 * Last modified: 2020.07.18 at 19:55
 */

declare(strict_types=1);


namespace LaborDigital\T3SAI\Event;


class FindByQueryAfterFetchEvent extends AbstractFindByQueryEvent
{
    /**
     * The list of rows returned by the database
     *
     * @var array
     */
    protected $rows;
    
    /**
     * @inheritDoc
     */
    public function __construct(string $searchString, array $searchWords, array $options, array $results)
    {
        parent::__construct($searchString, $searchWords, $options);
        $this->rows = $results;
    }
    
    /**
     * Returns the list of rows returned by the database
     *
     * @return array
     */
    public function getRows(): array
    {
        return $this->rows;
    }
    
    /**
     * Updates the list of rows
     *
     * @param   array  $rows
     *
     * @return FindByQueryAfterFetchEvent
     */
    public function setRows(array $rows): FindByQueryAfterFetchEvent
    {
        $this->rows = $rows;
        
        return $this;
    }
}
