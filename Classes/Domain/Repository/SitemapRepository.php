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


use LaborDigital\T3ba\Tool\Database\DbService;
use Neunerlei\Inflection\Inflector;

class SitemapRepository
{
    public const TABLE_SITEMAP = 'tx_search_and_index_sitemaps';
    
    /**
     * @var \LaborDigital\T3ba\Tool\Database\DbService
     */
    protected $db;
    
    public function __construct(DbService $db)
    {
        $this->db = $db;
    }
    
    /**
     * Persists a single sitemap into the database
     *
     * @param   string  $identifier
     * @param   string  $content
     *
     * @return void
     */
    public function persistSitemap(string $identifier, string $content): void
    {
        $id = Inflector::toUuid($identifier);
        $query = $this->db->getQuery(static::TABLE_SITEMAP);
        
        $query->runInTransaction(static function () use ($query, $id, $content) {
            $query->withWhere(['id' => $id])->delete();
            $query->insert(['id' => $id, 'sitemap' => $content]);
        });
    }
    
    /**
     * Finds a specific sitemap in the storage table
     *
     * @param   string  $identifier
     *
     * @return string|null
     */
    public function findSitemap(string $identifier): ?string
    {
        $id = Inflector::toUuid($identifier);
        
        $query = $this->db->getQuery(static::TABLE_SITEMAP);
        $result = $query->withWhere(['id' => $id])->getFirst();
        
        if (! $result) {
            return null;
        }
        
        return $result['sitemap'];
    }
    
    /**
     * Removes all sitemaps from the persistence table
     *
     * @return void
     */
    public function flushAll(): void
    {
        $this->db->getQuery(static::TABLE_SITEMAP)->delete();
    }
}