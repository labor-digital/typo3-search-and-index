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
use TYPO3\CMS\Core\Database\Query\QueryBuilder;

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
                 $this->expr()->sum('r.exact_word_matches') . ' AS foo ' .
                 ', ' .
                 'GROUP_CONCAT(DISTINCT ' . $this->qi('r.word') . ') AS ' . $this->qi('distinct_words') .
                 ', ' .
                 // The number of exact matches in a WORD = multiplied by the priority +80 points each
                 // This number is a bit finicky and tries to balance out "nerfed" words against exact matches
                 // inside the content
                 '(' .
                 $this->expr()->sum('r.exact_word_matches') . ' * 100' .
                 ') + ' .
                 // The exact match of a whole input string in a NODE = +125 points
                 '(' .
                 $this->expr()->sum('r.exact_content_matches') . ' * 75' .
                 ') + ' .
                 // The exact match of a whole input string in a NODE's TITLE = +175 points
                 '(' .
                 $this->expr()->sum('r.exact_title_matches') . ' * 175' .
                 ') + ' .
                 // Match on a key word was found = this is important!
                 '(' .
                 $this->expr()->sum('r.key_word_matches') . ' * 200' .
                 ') + ' .
                 $this->expr()->max('r.node_priority') . ' + ' .
                 // Add the average word priority to the node priority
                 '(' .
                 $this->expr()->sum('r.word_priority') . ' / ' . $this->expr()->sum('r.word_count') .
                 ') ' .
                 'AS ' . $this->qi('priority') . ' ' .
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
        $qbs = [];
        
        // Find nodes through word table...
        $qb = $this->makeQueryBuilderClone();
        $qb->from(static::TABLE_NODES, 'n')->select('n.*');
        $this->applySelectFields($qb, [
            'word_count' => '1',
            'word_priority' => $this->qi('w.priority'),
            'word' => $this->qi('w.word'),
            'key_word_matches' => $this->qi('w.is_keyword'),
        ]);
        $qb->where($this->buildWordWhere(true))
           ->getConcreteQueryBuilder()
           ->leftJoin($this->qi('n'), static::TABLE_WORDS, $this->qi('w'), 'n.id = w.id');
        $qbs[] = $qb;
        
        // Find nodes through word table BY an EXACT MATCH -> higher priority
        $qb = $this->makeQueryBuilderClone();
        $qb->from(static::TABLE_NODES, 'n')->select('n.*');
        $this->applySelectFields($qb, [
            'word_count' => '1',
            'exact_word_matches' => '1',
            'word' => $this->qi('w.word'),
        ]);
        $qb->where($this->buildWordWhere(false))
           ->getConcreteQueryBuilder()
           ->leftJoin($this->qi('n'), static::TABLE_WORDS, $this->qi('w'), 'n.id = w.id');
        $qbs[] = $qb;
        
        // Find nodes by exact match...
        $qb = $this->makeQueryBuilderClone();
        $qb->from(static::TABLE_NODES, 'n')->select('*');
        $this->applySelectFields($qb, ['exact_content_matches' => '1']);
        
        $wordListConstraints = [];
        foreach ($this->input->requiredWordLists as $wordList) {
            $wordListConstraints[] = $this->expr()->like('content', $this->q('%' . $wordList . '%'));
        }
        $qb->where($this->expr()->orX(...$wordListConstraints));
        $qbs[] = $qb;
        
        // Find nodes by exact match in the title... -> super high priority
        $qb = $this->makeQueryBuilderClone();
        $qb->from(static::TABLE_NODES, 'n')->select('*');
        $this->applySelectFields($qb, ['exact_title_matches' => '1']);
        
        $wordListConstraints = [];
        foreach ($this->input->requiredWordLists as $wordList) {
            $wordListConstraints[] = $this->expr()->like('title', $this->q('%' . $wordList . '%'));
        }
        $qb->where($this->expr()->orX(...$wordListConstraints));
        $qbs[] = $qb;
        
        return implode(' UNION ', $qbs);
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
    
    protected function buildWordWhere(bool $fuzzy): string
    {
        $words = $this->resolveRequiredWords();
        $soundexGenerator = $this->request->getSoundexGenerator();
        
        if (empty($words)) {
            return '1 = 2';
        }
        
        $wordConditions = [];
        foreach ($words as $word) {
            if ($fuzzy) {
                $wordConditions[] = $this->expr()->like('w.word', $this->q($word));
                $wordConditions[] = $this->expr()->like('w.word', $this->q($word . '%'));
                $wordConditions[] = $this->expr()->like('w.soundex', $this->q($soundexGenerator->generate($word)));
                
                if (strlen($word) > 5) {
                    $wordConditions[] = $this->expr()->like('w.word', $this->q('%' . $word . '%'));
                }
            } else {
                $wordConditions[] = $this->expr()->eq('w.word', $this->q($word));
            }
            
        }
        
        $where = $this->expr()->orX(...$wordConditions);
        
        return (string)$this->expr()->andX(
            $this->wrapQueryWithRequestConstraints($where, static::TABLE_WORDS, 'w'),
            $this->expr()->gt('w.priority', '0.1')
        );
    }
    
    protected function makeQueryBuilderClone(): QueryBuilder
    {
        $qb = clone $this->qb;
        $qb->resetQueryParts();
        
        return $qb;
    }
    
    protected function applySelectFields(QueryBuilder $queryBuilder, array $values): void
    {
        $fields = [
            'exact_title_matches' => '0',
            'exact_content_matches' => '0',
            'exact_word_matches' => '0',
            'key_word_matches' => '0',
            'word_count' => '0',
            'word_priority' => '0',
            'word' => 'NULL',
            'node_priority' => $this->qi('n.priority'),
        ];
        
        foreach ($fields as $alias => $value) {
            $value = $values[$alias] ?? $value;
            $queryBuilder->addSelectLiteral($value . ' AS ' . $this->qi($alias));
        }
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