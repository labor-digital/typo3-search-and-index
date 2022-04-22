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
use LaborDigital\T3sai\Search\Indexer\Page\ContentElement\PageContentProcessor;
use LaborDigital\T3sai\Search\Indexer\Page\PageContent\PageContentResolver;
use LaborDigital\T3sai\Search\Indexer\Page\PageData\PageDataMapper;
use LaborDigital\T3sai\Search\Indexer\Page\PageData\PageDataResolver;
use LaborDigital\T3sai\Search\Indexer\RecordIndexerInterface;
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
    protected $ceResolver;
    
    /**
     * @var \LaborDigital\T3sai\Search\Indexer\Page\ContentElement\PageContentProcessor
     */
    protected $ceProcessor;
    
    /**
     * @var \LaborDigital\T3sai\Search\Indexer\Page\PageData\PageDataMapper
     */
    protected $dataMapper;
    
    /**
     * @var \Psr\EventDispatcher\EventDispatcherInterface
     */
    protected $eventDispatcher;
    
    public function __construct(
        PageDataResolver $dataResolver,
        PageDataMapper $dataMapper,
        PageContentResolver $ceResolver,
        PageContentProcessor $ceProcessor,
        EventDispatcherInterface $eventDispatcher
    )
    {
        $this->dataResolver = $dataResolver;
        $this->dataMapper = $dataMapper;
        $this->ceResolver = $ceResolver;
        $this->ceProcessor = $ceProcessor;
        $this->eventDispatcher = $eventDispatcher;
    }
    
    /**
     * Injects the configured options, provided when the indexer was registered in the domain
     *
     * @param   array|null  $options
     *                              - rootPids array: By default, the indexer will start at the root pid of the domain's site,
     *                              but you can also add your own root pids to override that behaviour. T3BA PID References are supported here.
     *                              - titleCol string (title): The db column where the title attribute should be read from
     *                              - titleFallbackCol string (title): The db column where the title attribute should be read from if the main title column contained an empty value
     *                              - descriptionCol string (description): The db column where the description should be read from
     *                              - imageCol string (media): A file reference column which points to the media data that should be used as preview image
     *                              - searchCols array (og_description, og_title, twitter_title, twitter_description):
     *                              A list of additional columns to load from the "pages" database table and add to the searchable content
     *
     * @return void
     */
    public function setOptions(?array $options): void
    {
        $this->options = Options::make($options ?? [], [
            'rootPids' => [
                'type' => 'array',
                'default' => [],
            ],
            'titleCol' => [
                'type' => 'string',
                'default' => 'title',
            ],
            'titleFallbackCol' => [
                'type' => 'string',
                'default' => 'title',
            ],
            'descriptionCol' => [
                'type' => 'string',
                'default' => 'description',
            ],
            'imageCol' => [
                'type' => 'string',
                'default' => 'media',
            ],
            'searchCols' => [
                'type' => 'array',
                'default' => [
                    'og_description',
                    'og_title',
                    'twitter_title',
                    'twitter_description',
                ],
            ],
        ]);
    }
    
    /**
     * @inheritDoc
     */
    public function resolve(QueueRequest $request): iterable
    {
        $this->ceProcessor->initializeIndexers(
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
        $this->dataMapper->mapPageToNode(
            $node,
            $element,
            $this->options
        );
        
        foreach ($this->generateContents($element, $request) as $content) {
            $node->addContent($content);
        }
        
        $node->setTimestamp($this->ceProcessor->getLatestTimestamp());
        
        $this->eventDispatcher->dispatch(new PageIndexNodeFilterEvent($request, $element, $this->options));
    }
    
    /**
     * Generates the list of content element contents of the page
     *
     * @param   array                                                $element
     * @param   \LaborDigital\T3sai\Core\Indexer\Queue\QueueRequest  $request
     *
     * @return array
     */
    protected function generateContents(array $element, QueueRequest $request): array
    {
        return $this->ceProcessor->generateContent(
            $this->ceResolver->makeContentIterator($element['uid'] ?? 0),
            $request
        );
    }
    
}