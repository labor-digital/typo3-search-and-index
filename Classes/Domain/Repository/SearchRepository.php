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

namespace LaborDigital\T3SAI\Domain\Repository;

use LaborDigital\T3SAI\Domain\Entity\PersistableNodeList;
use LaborDigital\T3SAI\Event\FindByQueryAfterFetchEvent;
use LaborDigital\T3SAI\Event\FindByQueryAfterProcessingEvent;
use LaborDigital\T3SAI\Event\FindByQueryInputFilterEvent;
use LaborDigital\T3SAI\Indexer\ContentParser;
use LaborDigital\T3SAI\Util\SearchQueryBuilder;
use LaborDigital\Typo3BetterApi\Container\CommonDependencyTrait;
use LaborDigital\Typo3BetterApi\Domain\BetterQuery\StandaloneBetterQuery;
use Neunerlei\Options\Options;

class SearchRepository
{
    use CommonDependencyTrait;
    
    public const TABLE_NODES = 'tx_search_and_index_nodes';
    public const TABLE_WORDS = 'tx_search_and_index_words';
    
    /**
     * Persists the the given node list into the database tables.
     *
     * @param   \LaborDigital\T3SAI\Domain\Entity\PersistableNodeList  $nodeList
     */
    public function persistNodes(PersistableNodeList $nodeList): void
    {
        // Persist the new nodes
        $nodeQuery = $this->Db()->getQuery(static::TABLE_NODES);
        foreach (array_chunk($nodeList->getNodeRows(), 25) as $chunk) {
            $nodeQuery->runInTransaction(static function () use ($chunk, $nodeQuery) {
                foreach ($chunk as $row) {
                    $nodeQuery->insert($row);
                }
            });
        }
        
        // Persist the new words
        $wordQuery = $this->Db()->getQuery(static::TABLE_WORDS);
        foreach (array_chunk($nodeList->getWordRows(), 50) as $chunk) {
            $nodeQuery->runInTransaction(static function () use ($chunk, $wordQuery) {
                foreach ($chunk as $row) {
                    $wordQuery->insert($row);
                }
            });
        }
    }
    
    /**
     * Removes all nodes and keywords which are currently active and sets all non active rows to be active.
     * This should prevent any noticeable downtime of the search engine
     */
    public function activateNewNodesAndRemoveOldOnes(): void
    {
        // Update the node table
        $this->Db()
             ->getQuery(static::TABLE_NODES)
             ->runInTransaction(static function (StandaloneBetterQuery $query) {
                 $query->withWhere(['active' => '1'])->delete();
                 $query->update(['active' => '1']);
             });
        
        // Update the word table
        $this->Db()
             ->getQuery(static::TABLE_WORDS)
             ->runInTransaction(static function (StandaloneBetterQuery $query) {
                 $query->withWhere(['active' => '1'])->delete();
                 $query->update(['active' => '1']);
             });
    }
    
    /**
     * Removes all nodes that are currently marked as inactive.
     * Those are probably remnants of a previous run which failed horribly
     */
    public function removeInactiveNodes(): void
    {
        // Update the node table
        $this->Db()
             ->getQuery(static::TABLE_NODES)
             ->withWhere(['active !=' => '1'])->delete();
        
        // Update the word table
        $this->Db()
             ->getQuery(static::TABLE_WORDS)
             ->withWhere(['active !=' => '1'])->delete();
    }
    
    /**
     * Returns a multidimensional array of index nodes.
     * The nodes are prepared for the extension's SiteMapGenerator class.
     *
     * @param   string  $domain
     *
     * @return array
     */
    public function findSiteMap(string $domain = 'default'): array
    {
        $result = $this->Db()
                       ->getQuery(static::TABLE_NODES)
                       ->withWhere([
                           'add_to_sitemap >' => '0',
                           'active'           => '1',
                           'domain'           => $domain,
                       ])
                       ->withOrder('priority', 'desc')
                       ->getAll(['url', 'priority', 'timestamp', 'image']);
        
        if (! is_array($result)) {
            $result = [];
        }
        
        return $result;
    }
    
    /**
     * Returns a list of all currently stored tags in the database
     *
     * @return array
     */
    public function findAllTags(): array
    {
        return $this->Db()->getQuery(static::TABLE_NODES)->withWhere(['active' => '1'])->getAll(['DISTINCT tag']);
    }
    
    /**
     * Finds matching search results based on the given query string.
     *
     * @param   string  $queryString  The raw search value that was entered by the user.
     * @param   array   $options      Additional options:
     *                                - language int (0): The language uid to limit the search to
     *                                - tags array: A list of tags to limit the search to. The tags are combined using an
     *                                OR operator.
     *                                - limit int (20): The maximal number of search results returned by this method
     *                                - additionalLimiterQuery string: Additional "HAVING" condition which limits the
     *                                number of outputed results. Should start with an OR or an AND, will be combined with
     *                                the $limit and can not override it!
     *                                - contentMatchLength int(400): The max number of characters the contentMatch result
     *                                field should contain
     *
     * @return array
     */
    public function findByQuery(string $queryString, array $options = []): array
    {
        // Prepare the options
        $options = Options::make($options, [
            'language'               => [
                'type'    => 'int',
                'default' => 0,
            ],
            'tags'                   => [
                'type'    => 'array',
                'default' => [],
            ],
            'limit'                  => [
                'type'    => 'int',
                'default' => 20,
            ],
            'additionalLimiterQuery' => [
                'type'    => 'string',
                'default' => '',
            ],
            'contentMatchLength'     => [
                'type'    => 'int',
                'default' => 400,
            ],
            'domain'                 => [
                'type'    => 'string',
                'default' => 'default',
                'filter'  => function ($v) {
                    return preg_replace('~[^A-Za-z0-9\-_]~i', '', $v);
                },
            ],
        ]);
        
        // Prepare the search
        ['text' => $searchString, 'words' => $searchWords]
            = $this->getSingletonOf(ContentParser::class)->parseInputList([[$queryString, 0]]);
        $searchWords = array_keys($searchWords);
        
        // Skip if the query is empty
        if (empty($searchWords) || (count($searchWords) < 3 && strlen(implode($searchWords)) < 2)) {
            return [];
        }
        
        // Allow filtering
        $this->EventBus()->dispatch(($e = new FindByQueryInputFilterEvent($searchString, $searchWords, $options)));
        $searchString = $e->getSearchString();
        $searchWords  = $e->getSearchWords();
        $options      = $e->getOptions();
        
        // Build the query as a SQL string
        $queryBuilder = $this->Db()->getQueryBuilder(SearchRepository::TABLE_NODES);
        $queries      = $this->getSingletonOf(SearchQueryBuilder::class)
                             ->buildQueries($queryBuilder, $searchString, $searchWords, $options);
        
        // Perform the queries and get the result list
        $connection = $queryBuilder->getConnection();
        $connection->beginTransaction();
        $lastStatement = null;
        foreach ($queries as $query) {
            $lastStatement = $connection->executeQuery($query);
        }
        
        // Allow filtering
        $this->EventBus()->dispatch(($e = new FindByQueryAfterFetchEvent(
            $searchString, $searchWords, $options, $lastStatement->fetchAll()
        )));
        $rows = $e->getRows();
        
        // Nothing to do if we have nothing to do...
        if (empty($rows)) {
            return [];
        }
        
        // Run trough the results and apply some post processing
        $results = [];
        foreach ($rows as $k => $r) {
            // Find matchable words
            $c = preg_replace('~(' . implode('|', $searchWords) . ')~ui', '[match]$1[/match]', $r['content']);
            
            // Attach similar sounding words to the word list if we did not find a match
            if (stripos($c, '[match]') === false) {
                $rowWords           = array_unique(explode(' ', $r['content']));
                $searchWordsSoundex = array_map('soundex', $searchWords);
                foreach ($rowWords as $rowWord) {
                    if (in_array(soundex($rowWord), $searchWordsSoundex)) {
                        $searchWords[] = $rowWord;
                    }
                }
                $searchWords = array_unique($searchWords);
                
                // Retry with the new search words
                $c = preg_replace('~(' . implode('|', $searchWords) . ')~ui', '[match]$1[/match]', $r['content']);
            }
            
            // Extract the matching content snippet
            $r['contentMatch'] = '';
            if (($pos = stripos($c, '[match]')) !== false) {
                $pos    = max(0, $pos - $options['contentMatchLength'] / 4);
                $cParts = explode(' ', substr($c, $pos));
                // Remove probably broken first word when this is not the start of the string
                if ($pos !== 0) {
                    array_shift($cParts);
                }
                $c = '';
                while (strlen($c) <= $options['contentMatchLength'] && ! empty($cParts)) {
                    $c .= rtrim(' ' . trim(array_shift($cParts), ' .,'));
                }
                $r['contentMatch'] = ($pos === 0 ? '' : '...') . trim($c) . (empty($cParts) ? '' : '...');
            }
            
            // Unpack the meta data
            $r['metaData'] = json_decode($r['meta_data'], true, 512, JSON_THROW_ON_ERROR);
            unset($r['meta_data']);
            
            $results[$r['id']] = $r;
        }
        
        // Allow filtering
        $this->EventBus()->dispatch(($e = new FindByQueryAfterProcessingEvent(
            $searchString, $searchWords, $options, $results
        )));
        
        return $e->getResults();
    }
}
