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
 * Last modified: 2022.04.25 at 13:01
 */

declare(strict_types=1);


namespace LaborDigital\T3sai\Api\Resource;


use LaborDigital\T3ba\ExtConfig\SiteBased\SiteConfigContext;
use LaborDigital\T3ba\Tool\OddsAndEnds\SerializerUtil;
use LaborDigital\T3fa\Core\Resource\AbstractResource;
use LaborDigital\T3fa\Core\Resource\Query\ResourceQuery;
use LaborDigital\T3fa\Core\Resource\Repository\Context\ResourceCollectionContext;
use LaborDigital\T3fa\Core\Resource\Repository\Context\ResourceContext;
use LaborDigital\T3fa\ExtConfigHandler\Api\Resource\ResourceConfigurator;
use LaborDigital\T3sai\Domain\Repository\SearchRepository;
use Neunerlei\Inflection\Inflector;
use Psr\EventDispatcher\EventDispatcherInterface;

class SearchAutocompleteResource extends AbstractResource
{
    use SearchResourceArgumentsTrait;
    
    /**
     * @var \LaborDigital\T3sai\Domain\Repository\SearchRepository
     */
    protected $repository;
    
    /**
     * @var \Psr\EventDispatcher\EventDispatcherInterface
     */
    protected $eventDispatcher;
    
    public function __construct(
        SearchRepository $repository,
        EventDispatcherInterface $eventDispatcher
    )
    {
        $this->repository = $repository;
        $this->eventDispatcher = $eventDispatcher;
    }
    
    /**
     * @inheritDoc
     */
    public static function configure(ResourceConfigurator $configurator, SiteConfigContext $context): void
    {
        $configurator->setCacheEnabled(false);
    }
    
    /**
     * @inheritDoc
     */
    public function findSingle($id, ResourceContext $context)
    {
        return null;
    }
    
    /**
     * @inheritDoc
     */
    public function findCollection(ResourceQuery $resourceQuery, ResourceCollectionContext $context)
    {
        [$input, $options] = $this->prepareArguments($resourceQuery, $context);
        
        $items = $this->repository->findAutocompleteResults($input, $options);
        $items = array_map(static function (array $item) {
            $item['id'] = Inflector::toUuid(SerializerUtil::serializeJson($item));
            
            return $item;
        }, $items);
        
        // @todo allow filtering
        return $items;
    }
    
    /**
     * Receives the resource query and the context to prepare the options and the query string
     * or the used repository methods
     *
     * @param   \LaborDigital\T3fa\Core\Resource\Query\ResourceQuery                           $resourceQuery
     * @param   \LaborDigital\T3fa\Core\Resource\Repository\Context\ResourceCollectionContext  $context
     *
     * @return array
     * @throws \League\Route\Http\Exception\BadRequestException
     */
    protected function prepareArguments(ResourceQuery $resourceQuery, ResourceCollectionContext $context): array
    {
        [$input, $options] = $this->prepareCommonArguments($resourceQuery);

//        $this->eve->dispatch(($e = new SearchResourceQueryFilterEvent(
//            $request, $context, $options, $query3
//        )));
//        $query = $e->getQuery();
//        $options = $e->getOptions();
        
        return [$input, $options];
    }
    
}