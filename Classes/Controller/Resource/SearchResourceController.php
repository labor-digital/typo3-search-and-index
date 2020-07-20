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
 * Last modified: 2019.09.01 at 23:58
 */

namespace LaborDigital\T3SAI\Controller\Resource;


use LaborDigital\T3SAI\Domain\Repository\SearchRepository;
use LaborDigital\T3SAI\Event\SearchResourcePostProcessorEvent;
use LaborDigital\T3SAI\Event\SearchResourceQueryFilterEvent;
use LaborDigital\Typo3BetterApi\ExtConfig\ExtConfigContext;
use LaborDigital\Typo3BetterApi\NotImplementedException;
use LaborDigital\Typo3FrontendApi\JsonApi\Configuration\ResourceConfigurator;
use LaborDigital\Typo3FrontendApi\JsonApi\Controller\AbstractResourceController;
use LaborDigital\Typo3FrontendApi\JsonApi\Controller\CollectionControllerContext;
use LaborDigital\Typo3FrontendApi\JsonApi\Controller\ResourceControllerContext;
use League\Route\Http\Exception\BadRequestException;
use Neunerlei\Arrays\Arrays;
use Psr\Http\Message\ServerRequestInterface;

class SearchResourceController extends AbstractResourceController
{
    /**
     * @var \LaborDigital\T3SAI\Domain\Repository\SearchRepository
     */
    protected $searchRepository;
    
    /**
     * @inheritDoc
     */
    public function __construct(SearchRepository $searchRepository)
    {
        $this->searchRepository = $searchRepository;
    }
    
    /**
     * @inheritDoc
     */
    public static function configureResource(ResourceConfigurator $configurator, ExtConfigContext $context): void
    {
        $configurator->setAllowedProperties([
            'id',
            'title',
            'description',
            'image',
            'url',
            'lang',
            'tag',
            'contentMatch',
            'metaData',
        ]);
    }
    
    /**
     * @inheritDoc
     */
    public function resourceAction(ServerRequestInterface $request, int $id, ResourceControllerContext $context)
    {
        throw new NotImplementedException();
    }
    
    /**
     * @inheritDoc
     */
    public function collectionAction(ServerRequestInterface $request, CollectionControllerContext $context)
    {
        $args = $request->getQueryParams();
        if (! isset($args['query'])) {
            throw new BadRequestException('The query parameter is missing!');
        }
        
        // Prepare the query
        $query = urldecode((string)$args['query']);
        
        // Use the query parameters to configure the repository
        $options = [];
        if (! empty($args['L'])) {
            $options['language'] = (int)$args['L'];
        }
        if (! empty($args['tags'])) {
            $options['tags'] = Arrays::makeFromStringList($args['tags']);
        }
        if (! empty($args['page']) && ! empty($args['page']['size'])) {
            $options['limit'] = max(1, min(100, (int)$args['page']['size']));
        }
        if (! empty($args['contentMatchLength'])) {
            $options['contentMatchLength'] = max(10, min(10000, (int)$args['contentMatchLength']));
        }
        if (! empty($args['domain'])) {
            $options['domain'] = $args['domain'];
        }
        
        // Allow filtering
        $this->EventBus()->dispatch(($e = new SearchResourceQueryFilterEvent(
            $request, $context, $options, $query
        )));
        $query   = $e->getQuery();
        $options = $e->getOptions();
        
        // Execute the query
        if (! empty($query)) {
            $result = $this->searchRepository->findByQuery($query, $options);
        } else {
            $result = [];
        }
        
        // Allow filtering
        $this->EventBus()->dispatch(($e = new SearchResourcePostProcessorEvent(
            $request, $context, $options, $query, $result
        )));
        
        // Done
        return $e->getResult();
    }
    
}
