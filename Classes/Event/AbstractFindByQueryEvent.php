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
 * Last modified: 2020.07.18 at 19:48
 */

declare(strict_types=1);


namespace LaborDigital\T3SAI\Event;


abstract class AbstractFindByQueryEvent
{
    /**
     * The complete search string given
     *
     * @var string
     */
    protected $searchString;
    
    /**
     * The searchString split up into an array of words
     *
     * @var array
     */
    protected $searchWords;
    
    /**
     * The options to use {@see SearchRepository::findByQuery()} for the possible options
     *
     * @var array
     */
    protected $options;
    
    /**
     * AbstractFindByQueryEvent constructor.
     *
     * @param   string  $searchString
     * @param   array   $searchWords
     * @param   array   $options
     */
    public function __construct(string $searchString, array $searchWords, array $options)
    {
        $this->searchString = $searchString;
        $this->searchWords  = $searchWords;
        $this->options      = $options;
    }
    
    /**
     * Returns the complete search string given
     *
     * @return string
     */
    public function getSearchString(): string
    {
        return $this->searchString;
    }
    
    /**
     * Returns the searchString split up into an array of words
     *
     * @return array
     */
    public function getSearchWords(): array
    {
        return $this->searchWords;
    }
    
    /**
     * Returns the currently set options to use {@see SearchRepository::findByQuery()} for the possible options
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }
}
