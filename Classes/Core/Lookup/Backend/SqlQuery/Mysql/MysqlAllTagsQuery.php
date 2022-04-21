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
 * Last modified: 2022.04.19 at 21:25
 */

declare(strict_types=1);


namespace LaborDigital\T3sai\Core\Lookup\Backend\SqlQuery\Mysql;


use LaborDigital\T3sai\Core\Lookup\Backend\SqlQuery\AbstractSqlQuery;
use LaborDigital\T3sai\Domain\Repository\SearchRepository;

class MysqlAllTagsQuery extends AbstractSqlQuery
{
    /**
     * @inheritDoc
     */
    public function build(): string
    {
        return $this->qb->select('tag')
                        ->from(SearchRepository::TABLE_NODES)
                        ->where($this->wrapQueryWithRequestConstraints('1=1'))
                        ->getConcreteQueryBuilder()
                        ->distinct()
                        ->getSQL();
    }
    
}