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
 * Last modified: 2022.04.14 at 17:11
 */

declare(strict_types=1);


namespace LaborDigital\T3sai\Core\Lookup\Backend\SqlQuery;


use LaborDigital\T3sai\Core\Lookup\Request\LookupRequest;
use TYPO3\CMS\Core\Database\Connection;

interface SqlQueryProviderInterface
{
    /**
     * Must check if the query provider can handle the current DB platform.
     *
     * @param   string                                                 $platformName
     * @param   \TYPO3\CMS\Core\Database\Connection                    $connection
     * @param   \LaborDigital\T3sai\Core\Lookup\Request\LookupRequest  $request
     *
     * @return bool
     */
    public function canHandle(string $platformName, Connection $connection, LookupRequest $request): bool;
    
    /**
     * Must provide the correct sql query for the given request type {@see LookupRequest::getType()}
     *
     * @param   \LaborDigital\T3sai\Core\Lookup\Request\LookupRequest  $request
     *
     * @return \LaborDigital\T3sai\Core\Lookup\Backend\SqlQuery\SqlQueryInterface
     */
    public function getQuery(LookupRequest $request): SqlQueryInterface;
}