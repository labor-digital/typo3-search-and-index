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
/**
 * Copyright 2019 LABOR.digital
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
 * Last modified: 2019.09.06 at 15:17
 */

namespace LaborDigital\T3SAI\Controller\Route;


use LaborDigital\T3SAI\Configuration\SearchConfigRepository;
use LaborDigital\T3SAI\Domain\Repository\SearchRepository;
use LaborDigital\T3SAI\Event\SiteMapRowFilterEvent;
use LaborDigital\T3SAI\Util\SiteMapGenerator;
use LaborDigital\Typo3FrontendApi\ApiRouter\Configuration\RouteCollector;
use LaborDigital\Typo3FrontendApi\ApiRouter\Controller\AbstractRouteController;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Error\Http\BadRequestException;
use function GuzzleHttp\Psr7\stream_for;

class SiteMapController extends AbstractRouteController
{
    
    /**
     * @var \LaborDigital\T3SAI\Util\SiteMapGenerator
     */
    protected $siteMapGenerator;
    
    /**
     * @var \LaborDigital\T3SAI\Domain\Repository\SearchRepository
     */
    protected $searchRepository;
    
    /**
     * @var \LaborDigital\T3SAI\Configuration\SearchConfigRepository
     */
    protected $configRepository;
    
    /**
     * SiteMapController constructor.
     *
     * @param   \LaborDigital\T3SAI\Util\SiteMapGenerator                 $siteMapGenerator
     * @param   \LaborDigital\T3SAI\Domain\Repository\SearchRepository    $searchRepository
     * @param   \LaborDigital\T3SAI\Configuration\SearchConfigRepository  $configRepository
     */
    public function __construct(SiteMapGenerator $siteMapGenerator, SearchRepository $searchRepository, SearchConfigRepository $configRepository)
    {
        $this->siteMapGenerator = $siteMapGenerator;
        $this->searchRepository = $searchRepository;
        $this->configRepository = $configRepository;
    }
    
    /**
     * @inheritDoc
     */
    public static function configureRoutes(RouteCollector $routes)
    {
        $routes->get('siteMap', 'siteMapAction');
    }
    
    /**
     * Renders the xml site map
     *
     * @param   \Psr\Http\Message\ServerRequestInterface  $request
     *
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \TYPO3\CMS\Core\Error\Http\BadRequestException
     */
    public function siteMapAction(ServerRequestInterface $request): ResponseInterface
    {
        // Get the domain
        $domain = $request->getQueryParams()['domain'];
        $domain = preg_replace('~[^A-Za-z0-9\-_]~i', '', $domain);
        if (empty($domain) || ! $this->configRepository->hasDomainConfig($domain)) {
            $domain = 'default';
        }
        
        // Get the search configuration
        if (! $this->configRepository->hasDomainConfig($domain)) {
            throw new BadRequestException('Unknown domain key: $domain given!');
        }
        $config = $this->configRepository->getDomainConfig($domain);
        
        // Get the siteMap
        $e = new SiteMapRowFilterEvent($this->searchRepository->findSiteMap($domain), $domain);
        $this->EventBus()->dispatch($e);
        $siteMap = $this->siteMapGenerator->build($e->getRows(), $config->getSiteMapOptions());
        
        // Build the response
        $response = $this->getResponse();
        $response = $response->withHeader('Content-type', 'text/xml');
        $response = $response->withBody(stream_for($siteMap));
        
        return $response;
    }
}
