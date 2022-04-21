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
 * Last modified: 2022.04.14 at 14:47
 */

declare(strict_types=1);


namespace LaborDigital\T3sai\Core\Lookup\Backend\SqlQuery\Mysql;

use LaborDigital\T3sai\Core\Lookup\Backend\SqlQuery\AbstractSqlQuery;
use LaborDigital\T3sai\Domain\Repository\SearchRepository;

class MysqlSearchSqlQuery extends AbstractSqlQuery
{
    /**
     * @inheritDoc
     */
    public function build(): string
    {
        return $this->buildNodeQuery();
    }
    
    protected function buildNodeQuery(): string
    {
        if (empty($this->request->getInput()->string)) {
            return 'SELECT * from ' . $this->qi(SearchRepository::TABLE_NODES) . ' WHERE 1 = 2';
        }
        
        $query = 'SELECT ' .
                 'r.* ' .
                 ', ' .
                 '(' .
                 $this->expr()->sum('r.exact_match_priority') . ' * 100' .
                 ') + ' .
                 $this->expr()->sum('r.node_priority') . ' + ' .
                 // Add the average word priority to the node priority
                 '(' .
                 '(' .
                 $this->expr()->sum('r.word_priority') . ' / ' . $this->expr()->sum('r.word_count') .
                 ') * ' . $this->expr()->sum('r.word_count') .
                 ') AS ' . $this->qi('priority') . ' ' .
                 'FROM(' . $this->buildNodeSubQuery() . ') ' . $this->qi('r') . ' ' .
                 'WHERE ' . $this->wrapQueryWithRequestConstraints('1 = 1', static::TABLE_NODES, 'r') . ' ' .
                 $this->request->getAdditionalWhere() . ' ' .
                 'GROUP BY ' . $this->qi('r.id') . ' ';
        
        if ($this->request->getMaxTagItems()) {
            $query .= 'ORDER BY ' . $this->qi('r.tag') . ' asc, ' . $this->qi('priority') . ' desc ';
            $query = $this->buildLimiterQueryWrap($query);
        } else {
            $query .= 'ORDER BY ' . $this->qi('priority') . 'desc ';
            
            if ($this->request->getMaxItems()) {
                $query .= 'LIMIT ' . abs($this->request->getMaxItems()) . ' ';
            }
            
            if ($this->request->getOffset() > 0) {
                $query .= 'OFFSET ' . abs($this->request->getOffset()) . ' ';
            }
        }
        
        return $query;
    }
    
    protected function buildNodeSubQuery(): string
    {
        $qb = clone $this->qb;
        $qb->resetQueryParts();
        
        $qb->from(static::TABLE_NODES, 'n')
           ->select('n.*')
           ->addSelectLiteral('0 as ' . $this->qi('exact_match_priority'))
           ->addSelectLiteral('1 as ' . $this->qi('word_count'))
           ->addSelectLiteral($this->qi('w.priority') . ' as ' . $this->qi('word_priority'))
           ->addSelectLiteral($this->qi('n.priority') . ' as ' . $this->qi('node_priority'))
           ->where($this->buildWordWhere())
           ->getConcreteQueryBuilder()
           ->leftJoin($this->qi('n'), static::TABLE_WORDS, $this->qi('w'), 'n.id = w.id');
        
        $qb2 = clone $this->qb;
        $qb2->resetQueryParts();
        $qb2->from(static::TABLE_NODES, 'n')
            ->select('*')
            ->addSelectLiteral('0 as ' . $this->qi('word_count'))
            ->addSelectLiteral('1 as ' . $this->qi('exact_match_priority'))
            ->addSelectLiteral('0 as ' . $this->qi('word_priority'))
            ->addSelectLiteral($this->qi('n.priority') . ' AS ' . $this->qi('node_priority'));
        
        $wordListConstraints = [];
        foreach ($this->input->requiredWordLists as $wordList) {
            $wordListConstraints[] = $this->expr()->like('content', $this->q('%' . $wordList . '%'));
        }
        $qb2->where($this->expr()->orX(...$wordListConstraints));
        
        return $qb . ' UNION(' . $qb2 . ')';
    }
    
    protected function buildLimiterQueryWrap(string $nodeQuery): string
    {
        $nodeQuery = str_replace(' ' . $this->qi('priority'), ' ' . $this->qi('n_priority'), $nodeQuery);
        
        return 'SELECT ' .
               $this->qi('rs') . '.* ' .
               ', ' .
               $this->qi('rs.n_priority') . ' AS ' . $this->qi('priority') . ' ' .
               ', ' .
               '@count := IF(@current_tag = ' . $this->qi('rs.tag') . ', @count + 1, 1) ' .
               'AS ' . $this->qi('row_number') . ' ' .
               ', @current_tag := ' . $this->qi('rs.tag') . ' ' .
               'FROM (' . $nodeQuery . ') ' . $this->qi('rs') . ' ' .
               'HAVING ' . $this->expr()->lte('row_number', $this->request->getMaxTagItems());
    }
    
    protected function buildWordWhere(): string
    {
        $words = $this->resolveRequiredWords();
        $soundexGenerator = $this->request->getSoundexGenerator();
        
        if (empty($words)) {
            return '1 = 2';
        }
        
        $wordConditions = [];
        foreach ($words as $word) {
            $wordConditions[] = $this->expr()->like('w.word', $this->q($word));
            $wordConditions[] = $this->expr()->like('w.word', $this->q($word . '%'));
            $wordConditions[] = $this->expr()->like('w.soundex', $this->q($soundexGenerator->generate($word) . ' %'));
            
            if (strlen($word) > 5) {
                $wordConditions[] = $this->expr()->like('w.word', $this->q('%' . $word . '%'));
            }
        }
        
        $where = $this->expr()->orX(...$wordConditions);
        $where = $this->wrapQueryWithRequestConstraints($where, static::TABLE_WORDS, 'w');
        
        return (string)$where;
    }
    
    protected function resolveRequiredWords(): array
    {
        $words = [];
        
        foreach ($this->input->requiredWordLists as $wordList) {
            foreach (explode(' ', $wordList) as $word) {
                $word = strtolower($word);
                $words[] = $word;
            }
        }
        
        return array_filter($words);
    }
    
    
}