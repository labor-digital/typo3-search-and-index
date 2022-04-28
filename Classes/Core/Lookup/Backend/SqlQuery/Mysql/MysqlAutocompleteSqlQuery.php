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
 * Last modified: 2022.04.14 at 16:58
 */

declare(strict_types=1);


namespace LaborDigital\T3sai\Core\Lookup\Backend\SqlQuery\Mysql;

use LaborDigital\T3sai\Core\Lookup\Backend\SqlQuery\AbstractSqlQuery;
use LaborDigital\T3sai\Domain\Repository\SearchRepository;

class MysqlAutocompleteSqlQuery extends AbstractSqlQuery
{
    /**
     * @inheritDoc
     */
    public function build(): string
    {
        // If the "lastWordList" is empty we can't find anything
        if (empty($this->input->lastWordList)) {
            return 'SELECT * FROM ' . $this->qi(static::TABLE_NODES) . ' WHERE 1 = 2';
        }
        
        $inputString = $this->input->string;
        
        $parts = explode(' ', $this->input->lastWordList);
        $lastWord = end($parts);
        
        if (str_ends_with($inputString, ' ')) {
            return $this->buildForNextWordAutoComplete($lastWord);
        }
        
        return $this->buildForWordAutoComplete($lastWord);
    }
    
    /**
     * Generates the query to autocomplete the current word
     *
     * @param   string  $lastWord
     *
     * @return string
     */
    protected function buildForWordAutoComplete(string $lastWord): string
    {
        $where = $this->wrapQueryWithRequestConstraints(
            $this->expr()->andX(
                $this->expr()->like('word', $this->q($lastWord . '%')),
                $this->expr()->neq('word', $this->q($lastWord))
            ),
            SearchRepository::TABLE_WORDS
        );
        
        $sortPriority
            = $this->qi('priority') . ' + ' . $this->expr()->count('word') . ' * 5 AS ' . $this->qi('sort_priority');
        
        $query = $this->qb->resetQueryParts()
                          ->select('*')
                          ->addSelectLiteral($sortPriority)
                          ->from(static::TABLE_WORDS)
                          ->where($where)
                          ->groupBy('word', 'soundex')
                          ->addOrderBy('sort_priority', 'desc');
        
        if ($this->request->getMaxItems()) {
            $query->setMaxResults($this->request->getMaxItems());
        }
        
        return (string)$query;
    }
    
    /**
     * Generates the query to suggest how the next word could look like
     *
     * @param   string  $lastWord
     *
     * @return string
     */
    protected function buildForNextWordAutoComplete(string $lastWord): string
    {
        $where = $this->wrapQueryWithRequestConstraints(
            $this->expr()->andX(
                $this->expr()->orX(
                    $this->expr()->like('content', $this->q('% ' . $lastWord . ' %')),
                    $this->expr()->like('content', $this->q($lastWord . ' %')),
                    $this->expr()->like('set_keywords', $this->q('% ' . $lastWord . ' %')),
                    $this->expr()->like('set_keywords', $this->q($lastWord . ' %'))
                ),
                // This will remove the content block separators
                $this->expr()->notLike('content', $this->q('% ' . $lastWord . ' [...%')),
                $this->expr()->notLike('content', $this->q($lastWord . ' [...%'))
            ),
            SearchRepository::TABLE_NODES
        );
        
        $query = $this->qb->resetQueryParts()
                          ->select('*')
                          ->from(static::TABLE_NODES)
                          ->where($where)
                          ->orderBy('priority', 'desc');
        
        if ($this->request->getMaxItems()) {
            // The processor removes stopwords from the list.
            // To keep the "maxItem" count consistent we add a "padding" of rows we request.
            // The processor will then drop off all rows that exceed the maxItems output
            $query->setMaxResults((int)($this->request->getMaxItems() + ceil($this->request->getMaxItems() * 0.5)));
        }
        
        return (string)$query;
    }
}