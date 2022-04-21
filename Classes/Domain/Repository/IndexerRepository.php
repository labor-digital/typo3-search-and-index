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
 * Last modified: 2022.04.20 at 15:41
 */

declare(strict_types=1);


namespace LaborDigital\T3sai\Domain\Repository;


use InvalidArgumentException;
use LaborDigital\T3ba\Tool\Database\BetterQuery\Standalone\StandaloneBetterQuery;
use LaborDigital\T3ba\Tool\Database\DbService;

class IndexerRepository
{
    
    public const TABLE_NODES = SearchRepository::TABLE_NODES;
    public const TABLE_WORDS = SearchRepository::TABLE_WORDS;
    
    /**
     * @var \LaborDigital\T3ba\Tool\Database\DbService
     */
    protected $db;
    
    public function __construct(DbService $db)
    {
        $this->db = $db;
    }
    
    /**
     * Internal api to persist multiple rows for a provided table name.
     *
     * @param   string  $tableName  either the node or the word table name
     * @param   array   $rows       The list of rows to be persisted
     *
     * @return void
     */
    public function persistRows(string $tableName, array $rows): void
    {
        if (! in_array($tableName, [static::TABLE_NODES, static::TABLE_WORDS], true)) {
            throw new InvalidArgumentException('Invalid table name: "' . $tableName . '" given!');
        }
        
        $query = $this->db->getQuery($tableName);
        foreach (array_chunk($rows, 50) as $chunk) {
            $query->runInTransaction(static function () use ($chunk, $query) {
                foreach ($chunk as $row) {
                    $query->insert($row);
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
        $this->db
            ->getQuery(static::TABLE_NODES)
            ->runInTransaction(static function (StandaloneBetterQuery $query) {
                $query->withWhere(['active' => '1'])->delete();
                $query->update(['active' => '1']);
            });
        
        // Update the word table
        $this->db
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
        $this->db
            ->getQuery(static::TABLE_NODES)
            ->withWhere(['active !=' => '1'])->delete();
        
        // Update the word table
        $this->db
            ->getQuery(static::TABLE_WORDS)
            ->withWhere(['active !=' => '1'])->delete();
    }
}