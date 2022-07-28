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
 * Last modified: 2022.04.12 at 16:58
 */

declare(strict_types=1);


namespace LaborDigital\T3sai\Search\Indexer\Page;


use LaborDigital\T3sai\Core\Indexer\Node\Node;
use LaborDigital\T3sai\Core\Indexer\Queue\QueueRequest;
use LaborDigital\T3sai\Event\PageIndexNodeFilterEvent;
use LaborDigital\T3sai\Event\PageListFilterEvent;
use LaborDigital\T3sai\Search\Indexer\Page\PageContent\PageContentIndexer;
use LaborDigital\T3sai\Search\Indexer\Page\PageContent\PageContentResolver;
use LaborDigital\T3sai\Search\Indexer\Page\PageData\PageDataIndexer;
use LaborDigital\T3sai\Search\Indexer\Page\PageData\PageDataResolver;
use LaborDigital\T3sai\Search\Indexer\RecordIndexerInterface;
use Neunerlei\Arrays\Arrays;
use Neunerlei\Options\Options;
use Psr\EventDispatcher\EventDispatcherInterface;

class PageIndexer implements RecordIndexerInterface
{
    protected $options;
    
    /**
     * @var \LaborDigital\T3sai\Search\Indexer\Page\PageData\PageDataResolver
     */
    protected $dataResolver;
    
    /**
     * @var \LaborDigital\T3sai\Search\Indexer\Page\PageContent\PageContentResolver
     */
    protected $contentResolver;
    
    /**
     * @var \LaborDigital\T3sai\Search\Indexer\Page\PageContent\PageContentIndexer
     */
    protected $contentIndexer;
    
    /**
     * @var \LaborDigital\T3sai\Search\Indexer\Page\PageData\PageDataIndexer
     */
    protected $dataIndexer;
    
    /**
     * @var \Psr\EventDispatcher\EventDispatcherInterface
     */
    protected $eventDispatcher;
    
    public function __construct(
        PageDataResolver $dataResolver,
        PageDataIndexer $dataIndexer,
        PageContentResolver $contentResolver,
        PageContentIndexer $contentIndexer,
        EventDispatcherInterface $eventDispatcher
    )
    {
        $this->dataResolver = $dataResolver;
        $this->dataIndexer = $dataIndexer;
        $this->contentResolver = $contentResolver;
        $this->contentIndexer = $contentIndexer;
        $this->eventDispatcher = $eventDispatcher;
    }
    
    /**
     * Injects the configured options, provided when the indexer was registered in the domain
     *
     * @param   array|null  $options
     *                              - rootPids array: By default, the indexer will start at the root pid of the domain's site,
     *                              but you can also add your own root pids to override that behaviour. T3BA PID References are supported here.
     *                              - titleCol array|string (title): The db columns where the title attribute should be read from
     *                              - descriptionCol array|string (description): The db columns where the description should be read from
     *                              - imageCol array|string (media): A file reference column which points to the media data that should be used as preview image
     *                              - searchCols array|string (og_description, og_title, twitter_title, twitter_description):
     *                              A list of additional columns to load from the "pages" database table and add to the searchable content
     *
     *                              DEPRECATED:
     *                             - titleFallbackCol string (title): Should be removed in v11, use an additional titleCol instead!
     *                             The db column where the title attribute should be read from if the main title column contained an empty value
     *
     * @return void
     */
    public function setOptions(?array $options): void
    {
        $listPreFilter = static function ($v) {
            if (is_string($v)) {
                return Arrays::makeFromStringList($v);
            }
            
            return $v;
        };
        
        $this->options = Options::make($options ?? [], [
            'rootPids' => [
                'type' => 'array',
                'default' => [],
            ],
            'titleCol' => [
                'type' => 'array',
                'preFilter' => $listPreFilter,
                'default' => ['title'],
            ],
            'titleFallbackCol' => [
                'type' => 'string',
                'default' => 'title',
                'filter' => function ($v) {
                    if ($v !== 'title') {
                        trigger_error(
                            'Deprecated usage of: ' . static::class . ' indexer options. "titleFallbackCol" is deprecated. Use an array for "titleCol" instead!',
                            E_USER_DEPRECATED
                        );
                    }
                    
                    return $v;
                },
            ],
            'descriptionCol' => [
                'type' => 'array',
                'preFilter' => $listPreFilter,
                'default' => ['description'],
            ],
            'imageCol' => [
                'type' => 'array',
                'preFilter' => $listPreFilter,
                'default' => ['media'],
            ],
            'searchCols' => [
                'type' => 'array',
                'preFilter' => $listPreFilter,
                'default' => [
                    'og_description',
                    'og_title',
                    'twitter_title',
                    'twitter_description',
                ],
            ],
        ]);
        
        $this->options['titleCol'][] = $this->options['titleFallbackCol'];
        unset($this->options['titleFallbackCol']);
    }
    
    /**
     * @inheritDoc
     */
    public function resolve(QueueRequest $request): iterable
    {
        $this->contentIndexer->initializeIndexers(
            $request->getDomainConfig()['indexer']['contentElement'] ?? []
        );
        
        $pages = $this->dataResolver->makeDataIterator($this->options['rootPids']);
        
        return $this->eventDispatcher->dispatch(
            new PageListFilterEvent($request, $pages, $this->options)
        )->getPages();
    }
    
    /**
     * @inheritDoc
     */
    public function index($element, Node $node, QueueRequest $request): void
    {
        $this->dataIndexer->index(
            $element,
            $node,
            $this->options
        );
        
        $this->contentIndexer->index(
            $this->contentResolver->makeContentIterator($element['uid'] ?? 0),
            $node,
            $request
        );
        
        $node->setTimestamp($this->contentIndexer->getLatestTimestamp());
        
        $this->eventDispatcher->dispatch(new PageIndexNodeFilterEvent($request, $element, $this->options));
    }
    
}