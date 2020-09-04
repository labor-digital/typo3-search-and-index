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
 * Last modified: 2020.07.18 at 13:51
 */

declare(strict_types=1);


namespace LaborDigital\T3SAI\Util;


use LaborDigital\T3SAI\Domain\Repository\SearchRepository;
use Neunerlei\Arrays\Arrays;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\SingletonInterface;

class SearchQueryBuilder implements SingletonInterface
{
    /**
     * The currently processed options
     *
     * @var array
     */
    protected $options;

    /**
     * The current query builder instance
     *
     * @var QueryBuilder
     */
    protected $qb;

    /**
     * The database connection
     *
     * @var \TYPO3\CMS\Core\Database\Connection
     */
    protected $connection;

    /**
     * True if we are working with a mysql / maria db
     *
     * @var bool
     */
    protected $isMysql = false;

    /**
     * True if we are working with a sql server hell-hole...
     *
     * @var bool
     */
    protected $isSqlServer = false;

    /**
     * True if the query should result the count of matched objects instead of the objects themselves
     *
     * @var bool
     */
    protected $isCountQuery = false;

    /**
     * True if mysql is used as database
     * and the old style row number generation should be used (using (at)order)
     *
     * @var bool
     */
    protected $useOldRowNumberGeneration = false;

    /**
     * Builds a list of SQL queries that must be executed in a single transaction.
     * The last query returns the search results
     *
     * @param   QueryBuilder  $queryBuilder  The query builder object for the NODE table
     * @param   string        $searchString  The complete search string given
     * @param   array         $searchWords   The searchString split up into an array of words
     * @param   array         $options       The options to use -> there is no validation in here,
     *                                       {@see SearchRepository::findByQuery()} for the possible options
     *
     * @return array
     */
    public function buildQueries(QueryBuilder $queryBuilder, string $searchString, array $searchWords, array $options = []): array
    {
        $this->initialize($queryBuilder, $options);

        $queries = [];
        if (! $this->isSqlServer) {
            $queries[] = 'set @num := 0, @order := 0, @type := \'\';';
        }
        $queries[] = $this->buildSortingPartial(
            $this->buildNodePartial(
                $searchString,
                $this->buildWordListPartial($searchWords)
            )
        );

        return $queries;
    }

    /**
     * Builds a list of SQL queries that must be executed in a single transaction.
     * The last query returns the count of all tags that got results
     *
     * @param   QueryBuilder  $queryBuilder  The query builder object for the NODE table
     * @param   string        $searchString  The complete search string given
     * @param   array         $searchWords   The searchString split up into an array of words
     * @param   array         $options       The options to use -> there is no validation in here,
     *                                       {@see SearchRepository::getCountForQuery()} for the possible options
     *
     * @return array
     */
    public function buildCountQueries(QueryBuilder $queryBuilder, string $searchString, array $searchWords, array $options = []): array
    {
        $this->initialize($queryBuilder, $options);
        $this->isCountQuery = true;

        $queries = [];
        if (! $this->isSqlServer) {
            $queries[] = 'set @num := 0, @order := 0, @type := \'\';';
        }
        $queries[] = $this->buildCountPartial(
            $this->buildSortingPartial(
                $this->buildNodePartial(
                    $searchString,
                    $this->buildWordListPartial($searchWords)
                )
            )
        );

        return $queries;
    }

    protected function initialize(QueryBuilder $queryBuilder, array $options): void
    {
        $this->isCountQuery = false;
        $this->options      = $options;
        $this->qb           = $queryBuilder;
        $this->connection   = $queryBuilder->getConnection();
        $platformName       = $this->connection->getDriver()->getDatabasePlatform()->getName();
        $this->isMysql      = stripos($platformName, 'mysql') !== false || stripos($platformName, 'maria') !== false;
        $this->isSqlServer  = stripos($platformName, 'sqlserver') !== false || stripos($platformName, 'mssql') !== false;
        if ($this->isMysql) {
            $version = $this->connection->query('SHOW VARIABLES LIKE "version";')->fetchAll();
            $version = (is_array($version) ? Arrays::getPath($version, '0.Value') : null) ?? '5.6.0';
            preg_match('~\d+\.\d+\.\d+~', (string)$version, $m);
            $version = empty($m[0]) ? '5.6.0' : $m[0];
            if (version_compare($version, '8.0.0', '>=')) {
                $this->useOldRowNumberGeneration = false;
            }
        }
    }

    /**
     * Builds the "UNION" partial, for all given search words
     *
     * @param   array  $searchWords
     *
     * @return string
     */
    protected function buildWordListPartial(array $searchWords): string
    {
        $wordPartials = [];
        foreach ($searchWords as $searchWord) {
            $searchWord     = (string)$searchWord;
            $wordPartials[] = $this->buildSingleWordPartial($searchWord, false);
            if (strlen($searchWord) > 5) {
                $wordPartials[] = $this->buildSingleWordPartial($searchWord, true);
            }
        }

        return implode(PHP_EOL . ' UNION ' . PHP_EOL, $wordPartials);
    }

    /**
     * Builds the select query for a single word
     *
     * @param   string  $searchWord  The word to build the partial for
     * @param   bool    $fuzzy       True to build a "fuzzy" lookup using soundex comparison
     *
     * @return string
     */
    protected function buildSingleWordPartial(string $searchWord, bool $fuzzy): string
    {
        $wordLength      = strlen($searchWord);
        $wordLengthBonus = $wordLength > 6 ? $wordLength * 2 : 0;

        // Check on how to handle the query
        if ($fuzzy) {
            // Handle a fuzzy query
            $wordQuery = $this->qb->expr()->eq('soundex', $this->q(soundex($searchWord)));
        } elseif ($wordLength >= 5) {
            $wordQuery = $this->qb->expr()->like('word', $this->q('%' . $searchWord . '%'));
        } else {
            // or just use it as the "beginning" of another word
            $wordQuery = $this->qb->expr()->orX(
                $this->qb->expr()->like('word', $this->q($searchWord . '%')),
                $this->qb->expr()->like('word', $this->q(' ' . $searchWord . '%'))
            );
        }

        // Select the correct length function
        $lengthFunc = 'LENGTH';
        if ($this->isMysql) {
            $lengthFunc = 'CHAR_LENGTH';
        } elseif ($this->isSqlServer) {
            $lengthFunc = 'LEN';
        }

        // Select all word rows which match the given searchWord somehow
        return 'SELECT ' .
               $this->qi('id') . ' AS ' . $this->q('word_id') . ', ' .
               $this->qi('word') . ', ' .
               '(' .
               $this->qi('priority') . ' - (ABS(' . $lengthFunc . '(' . $this->qi('word') . ') - ' . $wordLength . ') * 5) + ' . $wordLengthBonus .
               ') * ' . ($this->isSqlServer ? 'iif' : 'if') .
               '(' . $this->qi('word') . ' = ' . $this->q($searchWord) . ', 2, 1)' .
               ($fuzzy ? ' * 0.45' : '') .
               ' AS ' . $this->qi('word_prio') .
               ' FROM ' . $this->qi(SearchRepository::TABLE_WORDS) .
               ' WHERE (' . $wordQuery . ')' .
               ' AND ' . $this->qi('lang') . ' = ' . $this->q($this->options['language']) .
               ' AND ' . $this->qi('active') . ' = 1';
    }

    /**
     * Builds the select query for a index node based on the given search string
     *
     * @param   string  $searchString
     * @param   string  $wordListPartial
     *
     * @return string
     */
    protected function buildNodePartial(string $searchString, string $wordListPartial): string
    {
        // Build the join sub-query
        $joinQuery = 'SELECT ' . $this->qi('word_id') . ',' .
                     ' SUM(' . $this->qi('r') . '.' . $this->qi('word_prio') . ') * COUNT(*) AS ' . $this->qi('word_prio') .
                     ' FROM ( ' . PHP_EOL .
                     $wordListPartial . PHP_EOL .
                     ' ) AS ' . $this->qi('r') .
                     ' GROUP BY ' . $this->qi('r') . '.' . $this->qi('word_id');

        // Build the outer query
        return 'SELECT *, ' .
               // We can only use the natural language matcher in a mysql enfiroment
               ($this->isMysql ? '(MATCH(' . $this->qi('content') . ') AGAINST (' . $this->q($searchString) . ' IN NATURAL LANGUAGE MODE) * 10) + ' : '') .
               $this->qi('w') . '.' . $this->qi('word_prio') . ' + ' .
               '(' .
               // If we can't use the natural language matcher -> compensate for our losses in priority score by pushing the node priority
               ($this->isMysql ? '2 *' : '6 *') .
               $this->qi('n') . '.' . $this->qi('priority') .
               ') AS ' . $this->qi('prio') .
               ' FROM ' . $this->qi(SearchRepository::TABLE_NODES) . ' AS ' . $this->qi('n') .
               ' JOIN (' . PHP_EOL .
               $joinQuery . PHP_EOL .
               ') ' . $this->qi('w') . ' ON ' . $this->qi('w') . '.' . $this->qi('word_id') . ' = ' . $this->qi('n') . '.' . $this->qi('id') .
               ' WHERE ' . $this->qi('lang') . ' = ' . $this->q($this->options['language']) .
               ' AND ' . $this->qi('active') . ' = 1' .
               ($this->isSqlServer ? '' : ' ORDER BY ' . $this->qi('prio') . ' DESC');

    }

    /**
     * Builds the outer query to sort and limit the results
     *
     * @param   string  $nodePartial
     *
     * @return string
     */
    protected function buildSortingPartial(string $nodePartial): string
    {
        // This query creates a new column called "ordering", so we can re-sort the result for our tag based limit
        // and then recreate the original ordering by priority
        $orderQuery = 'SELECT ' .
                      ($this->isSqlServer || ($this->isMysql && ! $this->useOldRowNumberGeneration)
                          ? (
                              ($this->isSqlServer ? 'TOP 500 *, ' : '*, ') .
                              'ROW_NUMBER() OVER(PARTITION BY ' . $this->qi('tag') . ' ORDER BY ' . $this->qi('r') . '.' .
                              $this->qi('prio') . ' DESC) AS ' . $this->qi('row_number') . ',' .
                              'ROW_NUMBER() OVER(ORDER BY ' . $this->qi('r') . '.' . $this->qi('prio') . ' DESC) AS ' . $this->qi('ordering')
                          )
                          : '*, @order := @order + 1 AS ' . $this->qi('ordering')
                      ) .
                      ' FROM ( ' . PHP_EOL .
                      $nodePartial . PHP_EOL .
                      ' ) AS ' . $this->qi('r');

        // Build the tag list
        $tagWhere = '';
        if (! empty($this->options['tags'])) {
            $tags = [];
            foreach ($this->options['tags'] as $tag) {
                $tags[] = $this->q(preg_replace('~[^a-zA-Z0-9\-_]~i', '', $tag));
            }
            $tagWhere = ' AND ' . $this->qi('r') . '.' . $this->qi('tag') . ' IN(' . implode(',', $tags) . ')';
        }

        // This query creates a new column called "row_number" which is used by the parent query's HAVING to limit
        // the result based on n rows by tag.
        if (! $this->useOldRowNumberGeneration) {
            $rowNumberQuery = $orderQuery;
        } else {
            $rowNumberQuery = 'SELECT *, ' .
                              '@num := if(@type = ' . $this->qi('r') . '.' . $this->qi('tag') . ', @num + 1, 1) as ' . $this->qi('row_number') . ',' .
                              '@type := ' . $this->qi('r') . '.' . $this->qi('tag') . ' as ' . $this->qi('dummy') .
                              ' FROM (' . PHP_EOL .
                              $orderQuery . PHP_EOL .
                              ') AS ' . $this->qi('r');
        }

        // Add the tag and domain constraint
        $rowNumberQuery .= ' WHERE 1=1 ' . $tagWhere . ' AND ' . $this->qi('domain') . ' = ' . $this->q($this->options['domain']);
        if ($this->useOldRowNumberGeneration) {
            $rowNumberQuery .= ' ORDER BY ' . $this->qi('r') . '.' . $this->qi('tag');
        }

        // Build the final query to limit the result
        $q = 'SELECT * FROM (' . PHP_EOL .
             $rowNumberQuery . PHP_EOL .
             ') AS ' . $this->qi('r');

        if ($this->options['maxTagItems'] !== 0) {
            $q .= ($this->isSqlServer ? ' WHERE ' : ' HAVING ') .
                  $this->qi('r') . '.' . $this->qi('row_number') . ' <= ' . $this->q($this->options['maxTagItems']);
        }

        if (! empty($this->options['additionalLimiterQuery'])) {
            $q .= PHP_EOL . $this->options['additionalLimiterQuery'];
        }

        $q .= ' ' . PHP_EOL . 'ORDER BY ' . $this->qi('ordering') . ' ASC, ' . $this->qi('r') . '.' . $this->qi('tag') . ' ASC';

        if (! $this->isCountQuery && $this->options['maxItems'] !== 0) {
            $q .= PHP_EOL . 'LIMIT ' . abs((int)$this->options['maxItems']);
            if ($this->options['offset'] !== 0) {
                $q .= PHP_EOL . 'OFFSET ' . abs((int)$this->options['offset']);
            }
        }

        return $q;
    }

    /**
     * Wraps the sorting partial to create a count of all existing tags
     *
     * @param   string  $sortingPartial
     *
     * @return string
     */
    protected function buildCountPartial(string $sortingPartial): string
    {
        return 'SELECT ' . $this->qi('tag') . ', COUNT(*) AS ' . $this->qi('count') . ' FROM (' . PHP_EOL .
               $sortingPartial . PHP_EOL .
               ') AS ' . $this->qi('r') . ' GROUP BY ' . $this->qi('r') . '.' . $this->qi('tag');
    }

    /**
     * Shortcut to quote a value
     *
     * @param $value
     *
     * @return string
     */
    protected function q($value): string
    {
        return $this->connection->quote($value);
    }

    /**
     * Shortcut to quote a single database identifier
     *
     * @param   string  $identifier
     *
     * @return string
     */
    protected function qi(string $identifier): string
    {
        return $this->connection->quoteIdentifier($identifier);
    }
}
