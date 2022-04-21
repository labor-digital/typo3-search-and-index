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
 * Last modified: 2022.04.14 at 17:20
 */

declare(strict_types=1);


namespace LaborDigital\T3sai\Core\Lookup\Backend\SqlQuery\Mysql;


use LaborDigital\T3ba\Core\Exception\NotImplementedException;
use LaborDigital\T3sai\Core\Lookup\Backend\SqlQuery\SqlQueryInterface;
use LaborDigital\T3sai\Core\Lookup\Backend\SqlQuery\SqlQueryProviderInterface;
use LaborDigital\T3sai\Core\Lookup\Request\LookupRequest;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class MysqlQueryProvider implements SqlQueryProviderInterface
{
    /**
     * @inheritDoc
     */
    public function canHandle(string $platformName, Connection $connection, LookupRequest $request): bool
    {
        return str_contains($platformName, 'mysql') || str_contains($platformName, 'maria');
    }
    
    /**
     * @inheritDoc
     */
    public function getQuery(LookupRequest $request): SqlQueryInterface
    {
        switch ($request->getType()) {
            case LookupRequest::TYPE_SEARCH:
                return GeneralUtility::makeInstance(MysqlSearchSqlQuery::class, $request);
            case LookupRequest::TYPE_AUTOCOMPLETE:
                return GeneralUtility::makeInstance(MysqlAutocompleteSqlQuery::class, $request);
            case LookupRequest::TYPE_TAGS:
                return GeneralUtility::makeInstance(MysqlAllTagsQuery::class, $request);
            case LookupRequest::TYPE_SEARCH_COUNT:
                return GeneralUtility::makeInstance(MysqlSearchCountSqlQuery::class, $request);
            default:
                throw new NotImplementedException('There is no query that supports request type: "' . $request->getType() . '"');
        }
    }
}