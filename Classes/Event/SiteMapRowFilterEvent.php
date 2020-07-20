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
 * Last modified: 2020.07.16 at 17:10
 */

declare(strict_types=1);


namespace LaborDigital\T3SAI\Event;

/**
 * Class SiteMapRowFilterEvent
 *
 * Dispatched in the siteMap api endpoint to filter the raw database rows before the xml is build
 *
 * @package LaborDigital\T3SAI\Event
 */
class SiteMapRowFilterEvent
{
    /**
     * The rows of all entries to show in the sitemap
     *
     * @var array
     */
    protected $rows;
    
    /**
     * The search domain to generate the sitemap for
     *
     * @var string
     */
    protected $domain;
    
    /**
     * SiteMapRowFilterEvent constructor.
     *
     * @param   array   $rows
     * @param   string  $domain
     */
    public function __construct(array $rows, string $domain)
    {
        $this->rows   = $rows;
        $this->domain = $domain;
    }
    
    /**
     * Returns the search domain to generate the sitemap for
     *
     * @return string
     */
    public function getDomain(): string
    {
        return $this->domain;
    }
    
    /**
     * Returns the rows of all entries to show in the sitemap
     *
     * @return array
     */
    public function getRows(): array
    {
        return $this->rows;
    }
    
    /**
     * Updates the rows of all entries to show in the sitemap
     *
     * @param   array  $rows
     *
     * @return SiteMapRowFilterEvent
     */
    public function setRows(array $rows): SiteMapRowFilterEvent
    {
        $this->rows = $rows;
        
        return $this;
    }
    
}
