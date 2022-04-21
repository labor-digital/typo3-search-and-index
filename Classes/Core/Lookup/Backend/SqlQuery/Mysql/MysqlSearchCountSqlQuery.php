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
 * Last modified: 2022.04.19 at 21:54
 */

declare(strict_types=1);


namespace LaborDigital\T3sai\Core\Lookup\Backend\SqlQuery\Mysql;


class MysqlSearchCountSqlQuery extends MysqlSearchSqlQuery
{
    /**
     * @inheritDoc
     */
    public function build(): string
    {
        $subQuery = parent::build();
        $subQuery = str_replace(' ' . $this->qi('priority'), ' ' . $this->qi('n_priority'), $subQuery);
        
        return 'SELECT ' .
               $this->qi('rc.tag') . ' ' .
               ', ' .
               $this->expr()->count('rc.tag') . ' AS ' . $this->qi('count') . ' ' .
               'FROM (' . $subQuery . ') ' . $this->qi('rc') . ' ' .
               'GROUP BY ' . $this->qi('rc.tag');
    }
    
}