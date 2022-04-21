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
 * Last modified: 2022.04.20 at 15:11
 */

declare(strict_types=1);


namespace LaborDigital\T3sai\Middleware;


use GuzzleHttp\Psr7\Utils;
use LaborDigital\T3ba\Core\Di\ContainerAwareTrait;
use LaborDigital\T3ba\Tool\OddsAndEnds\SerializerUtil;
use LaborDigital\T3sai\Core\Sitemap\SitemapResolver;
use LaborDigital\T3sai\Domain\Repository\SearchRepository;
use Neunerlei\Options\OptionValidationException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class T3saiMiddleware implements MiddlewareInterface
{
    use ContainerAwareTrait;
    
    /**
     * @var \Psr\Http\Message\ResponseFactoryInterface
     */
    protected $responseFactory;
    
    public function __construct(ResponseFactoryInterface $responseFactory)
    {
        $this->responseFactory = $responseFactory;
    }
    
    /**
     * @inheritDoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $uri = $request->getUri();
        if (! $uri) {
            return $handler->handle($request);
        }
        
        $path = $uri->getPath();
        
        if (str_starts_with($path, '/t3sai/')) {
            if (str_starts_with($path, '/t3sai/sitemap.xml')) {
                return $this->makeSitemapResponse($uri);
            }
            
            if (str_starts_with($path, '/t3sai/autocomplete') && $request->getMethod() === 'POST') {
                return $this->makeAutocompleteResponse((array)($request->getParsedBody() ?? []));
            }
        }
        
        return $handler->handle($request);
    }
    
    protected function makeSitemapResponse(UriInterface $uri): ResponseInterface
    {
        $sitemap = $this->makeInstance(SitemapResolver::class)->resolve($uri);
        
        if ($sitemap === null) {
            return $this->responseFactory->createResponse(404);
        }
        
        return $this->responseFactory->createResponse()
                                     ->withBody(Utils::streamFor($sitemap))
                                     ->withAddedHeader('Content-Type', 'application/xml;charset=utf-8')
                                     ->withAddedHeader('X-Robots-Tag', 'noindex');
    }
    
    protected function makeAutocompleteResponse(array $body): ResponseInterface
    {
        try {
            $result = $this->makeInstance(SearchRepository::class)
                           ->findAutocompleteResults(
                               (string)($body['search'] ?? ''),
                               ['domain' => $body['domain'] ?? null]
                           );
        } catch (OptionValidationException $e) {
            return $this->responseFactory->createResponse(403);
        }
        
        return $this->responseFactory->createResponse()
                                     ->withBody(Utils::streamFor(SerializerUtil::serializeJson($result)))
                                     ->withAddedHeader('Content-Type', 'application/json;charset=utf-8')
                                     ->withAddedHeader('X-Robots-Tag', 'noindex');
    }
}