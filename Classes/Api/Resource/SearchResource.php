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
 * Last modified: 2022.04.24 at 09:24
 */

declare(strict_types=1);


namespace LaborDigital\T3sai\Api\Resource;


use LaborDigital\T3ba\ExtConfig\SiteBased\SiteConfigContext;
use LaborDigital\T3fa\Core\Resource\AbstractResource;
use LaborDigital\T3fa\Core\Resource\Query\ResourceQuery;
use LaborDigital\T3fa\Core\Resource\Repository\Context\ResourceCollectionContext;
use LaborDigital\T3fa\Core\Resource\Repository\Context\ResourceContext;
use LaborDigital\T3fa\ExtConfigHandler\Api\Resource\ResourceConfigurator;
use LaborDigital\T3sai\Api\Util\ApiSearchResultPaginator;
use LaborDigital\T3sai\Core\Lookup\Backend\Processor\ProcessorUtilTrait;
use LaborDigital\T3sai\Domain\Repository\ImageRepository;
use LaborDigital\T3sai\Domain\Repository\SearchRepository;
use Neunerlei\Arrays\Arrays;
use Psr\EventDispatcher\EventDispatcherInterface;

class SearchResource extends AbstractResource
{
    use ProcessorUtilTrait;
    use SearchResourceArgumentsTrait;
    
    /**
     * @var \Psr\EventDispatcher\EventDispatcherInterface
     */
    protected $eventDispatcher;
    
    /**
     * @var ImageRepository|null
     */
    protected $imageRepository;
    
    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
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
        $args = $this->prepareArguments($resourceQuery, $context);
        [$input, $options] = $args;
        
        $this->processFlags($args, $context);
        
        return new ApiSearchResultPaginator(
            $input,
            $options,
            function (iterable $items) use ($resourceQuery) {
                return $this->processItemsForOutput($resourceQuery, $items);
            }
        );
    }
    
    /**
     * Checks if there were flags given in the "with" argument in the query
     *
     * @param   array                                                                          $args
     * @param   \LaborDigital\T3fa\Core\Resource\Repository\Context\ResourceCollectionContext  $context
     *
     * @return void
     */
    protected function processFlags(array $args, ResourceCollectionContext $context): void
    {
        if (empty($args[2])) {
            return;
        }
        
        [$input, $options, $flags] = $args;
        
        $meta = $context->getMeta();
        
        if (in_array('count', $flags, true)) {
            $meta['count'] = $this->getCounts($input, $options);
        }
        
        if (in_array('tags', $flags, true)) {
            $meta['tags'] = $this->getTags($options);
        }
        
        $context->setMeta($meta);
    }
    
    protected function getCounts(string $input, array $options): array
    {
        return $this->makeInstance(SearchRepository::class)->findSearchCounts($input, $options);
    }
    
    protected function getTags(array $options): array
    {
        unset($options['tags'], $options['maxTagItems'], $options['contentMatchLength']);
        
        return $this->makeInstance(SearchRepository::class)->findAllTags($options);
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
        $args = $resourceQuery->getAdditional();
        
        // Use the query parameters to configure the repository
        if (! empty($args['tags'])) {
            $options['tags'] = Arrays::makeFromStringList($args['tags']);
        }
        if (isset($args['maxTagItems'])) {
            $options['maxTagItems'] = max(1, min(5000, (int)$args['maxTagItems']));
        }
        if (! empty($args['contentMatchLength'])) {
            $options['contentMatchLength'] = max(10, min(10000, (int)$args['contentMatchLength']));
        }
        
        $flags = [];
        if (is_array($args['with'])) {
            $flags = $args['with'];
        } elseif (is_string($args['with'])) {
            $flags = Arrays::makeFromStringList($args['with']);
        }

//        $this->eve->dispatch(($e = new SearchResourceQueryFilterEvent(
//            $request, $context, $options, $query
//        )));
//        $query = $e->getQuery();
//        $options = $e->getOptions();
        
        return [$input, $options, $flags];
    }
    
    protected function processItemsForOutput(ResourceQuery $resourceQuery, iterable $items): array
    {
        $processedItems = Arrays::getList(
            $this->iterableToArray($items),
            [
                'id',
                'title',
                'description',
                'image',
                'url',
                'tag',
                'timestamp',
                'domain',
                'metaData',
                'contentMatch',
            ]
        );
        
        $includeSource = in_array('imageSource', $resourceQuery->getIncludedRelationships(), true);
        
        $processedItems = array_map(function (array $row, array $sourceRow) use ($includeSource) {
            if (is_string($row['timestamp'])) {
                $row['timestamp'] = new \DateTime($row['timestamp']);
            }
            
            // I know, this is not the best place to resolve the dependency,
            // this should under normal circumstances be done by the transformer,
            // however it is required here to allow the event to be able to filter the source.
            if ($includeSource) {
                $row['imageSource']
                    = $this->getImageRepository()->findBySource($sourceRow['image_source'] ?? '');
            }
            
            return $row;
        }, $processedItems, $items);
        
        // @todo add filter event
        
        return $processedItems;
    }
    
    /**
     * Lazily resolves the image repository only if needed by the processor
     *
     * @return \LaborDigital\T3sai\Domain\Repository\ImageRepository
     */
    protected function getImageRepository(): ImageRepository
    {
        return $this->imageRepository ??
               ($this->imageRepository = $this->makeInstance(ImageRepository::class));
    }
    
}