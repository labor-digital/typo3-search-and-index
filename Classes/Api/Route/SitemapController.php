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
 * Last modified: 2022.04.25 at 13:46
 */

declare(strict_types=1);


namespace LaborDigital\T3sai\Api\Route;


use GuzzleHttp\Psr7\Utils;
use LaborDigital\T3fa\Core\Routing\Controller\AbstractRouteController;
use LaborDigital\T3sai\Core\Sitemap\SitemapResolver;
use League\Route\Http\Exception\NotFoundException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class SitemapController extends AbstractRouteController
{
    /**
     * @var \LaborDigital\T3sai\Core\Sitemap\SitemapResolver
     */
    protected $sitemapResolver;
    
    public function __construct(SitemapResolver $sitemapResolver)
    {
        $this->sitemapResolver = $sitemapResolver;
    }
    
    public function sitemapAction(ServerRequestInterface $request): ResponseInterface
    {
        $uri = $request->getUri();
        if (! $uri) {
            throw new NotFoundException();
        }
        
        $sitemap = $this->sitemapResolver->resolve($uri);
        
        if (! $sitemap) {
            throw new NotFoundException();
        }
        
        return $this->getResponse()
                    ->withBody(Utils::streamFor($sitemap))
                    ->withAddedHeader('Content-Type', 'application/xml;charset=utf-8')
                    ->withAddedHeader('X-Robots-Tag', 'noindex');
    }
}