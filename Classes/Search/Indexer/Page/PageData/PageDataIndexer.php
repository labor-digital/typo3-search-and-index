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
 * Last modified: 2022.04.12 at 20:17
 */

declare(strict_types=1);


namespace LaborDigital\T3sai\Search\Indexer\Page\PageData;


use LaborDigital\T3ba\Tool\Fal\FalService;
use LaborDigital\T3ba\Tool\Link\Link;
use LaborDigital\T3sai\Core\Indexer\Node\Node;
use Psr\EventDispatcher\EventDispatcherInterface;

class PageDataIndexer
{
    /**
     * @var \LaborDigital\T3ba\Tool\Fal\FalService
     */
    protected $falService;
    
    /**
     * @var \Psr\EventDispatcher\EventDispatcherInterface
     */
    protected $eventDispatcher;
    
    /**
     * @var Node
     */
    protected $node;
    
    /**
     * @var array
     */
    protected $data;
    
    /**
     * @var array
     */
    protected $options;
    
    public function __construct(FalService $falService, EventDispatcherInterface $eventDispatcher)
    {
        $this->falService = $falService;
        $this->eventDispatcher = $eventDispatcher;
    }
    
    public function index(array $data, Node $node, array $options): void
    {
        $this->node = $node;
        $this->data = $data;
        $this->options = $options;
        
        $this->mapTitle();
        $this->mapSearchColumns();
        $this->mapMetaData();
        $this->mapImage();
        
        unset($this->node, $this->data, $this->options);
    }
    
    protected function mapTitle(): void
    {
        $column = $this->resolveContentColumn($this->options['titleCol']);
        
        if (! $column) {
            return;
        }
        
        $this->node->setTitle((string)$this->data[$column]);
        foreach (array_unique($this->options['titleCol']) as $additionalColumn) {
            if ($additionalColumn === $column) {
                continue;
            }
            
            $this->node->addContent(trim((string)$this->data[$additionalColumn]), 20);
        }
    }
    
    protected function mapSearchColumns(): void
    {
        foreach ($this->options['searchCols'] as $column) {
            if (empty($this->data[$column])) {
                continue;
            }
            
            $this->node->addContent((string)$this->data[$column]);
        }
    }
    
    protected function mapMetaData(): void
    {
        $descriptionCol = $this->resolveContentColumn($this->options['descriptionCol']);
        $this->node
            ->setDescription((string)($this->data[$descriptionCol] ?? ''))
            ->setTag('page')
            ->addKeywords((string)($this->data['sai_keywords'] ?? ''))
            ->editLink(function (Link $link) {
                return $link->withPid((int)$this->data['uid']);
            })
            ->setPriority(((int)($this->data['sai_priority'] ?? 0) + 100) / 2);
        
        if (! empty($this->data['sai_sitemap'])) {
            $this->node->setAddToSiteMap(false);
        }
    }
    
    protected function mapImage(): void
    {
        foreach ($this->options['imageCol'] as $column) {
            if (empty($this->data[$column])) {
                continue;
            }
            
            $mediaList = $this->falService->getFile(
                (int)$this->data['uid'],
                'pages',
                $column,
                false
            );
            
            foreach ($mediaList as $media) {
                if ($this->falService->getFileInfo($media)->isImage()) {
                    $this->node->setImage($media);
                    break 2;
                }
            }
        }
    }
    
    protected function resolveContentColumn(array $columns): ?string
    {
        foreach ($columns as $column) {
            if (! empty($this->data[$column])) {
                return $column;
            }
        }
        
        return null;
    }
    
}