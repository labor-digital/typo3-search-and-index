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
 * Last modified: 2022.04.20 at 15:18
 */

declare(strict_types=1);


namespace LaborDigital\T3sai\Core\Sitemap;


use GuzzleHttp\Psr7\Query;
use LaborDigital\T3sai\Domain\Repository\SitemapRepository;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Core\SingletonInterface;

class SitemapResolver implements SingletonInterface
{
    /**
     * @var \LaborDigital\T3sai\Domain\Repository\SitemapRepository
     */
    protected $repository;
    
    public function __construct(SitemapRepository $repository)
    {
        $this->repository = $repository;
    }
    
    public function resolve(UriInterface $uri, ?string $fileIdentifier = null): ?string
    {
        $host = $uri->getHost();
        $query = Query::parse($uri->getQuery());
        $fileIdentifier = $this->findFileIdentifier($fileIdentifier, $query);
        
        if (empty($fileIdentifier)) {
            $content = $this->repository->findSitemap('/' . $host . '/sitemap.xml');
        } else {
            $content = $this->repository->findSitemap('/' . $host . '/' . $fileIdentifier . '.xml');
        }
        
        if (! $content) {
            return null;
        }
        
        return $this->applyPostProcessing($content, $uri, $query);
    }
    
    protected function applyPostProcessing(string $content, UriInterface $uri, array $query): string
    {
        return preg_replace_callback('~REL\\(\\((.*?)\\)\\)~', static function ($m) use ($uri, $query) {
            $query['child'] = basename((string)($m[1] ?? ''), '.xml');
            
            return $uri->withQuery(Query::build($query));
        }, $content);
    }
    
    protected function findFileIdentifier(?string $fileIdentifier, array $query): string
    {
        if (is_string($fileIdentifier)) {
            return $fileIdentifier;
        }
        
        if (is_string($query['child'] ?? null)) {
            return $query['child'];
        }
        
        return '';
    }
}