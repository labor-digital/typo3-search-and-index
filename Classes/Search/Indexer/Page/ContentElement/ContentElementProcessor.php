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
 * Last modified: 2022.04.12 at 20:42
 */

declare(strict_types=1);


namespace LaborDigital\T3sai\Search\Indexer\Page\ContentElement;


use LaborDigital\T3ba\Core\Di\ContainerAwareTrait;
use LaborDigital\T3sai\Core\Indexer\Node\Node;
use LaborDigital\T3sai\Search\Indexer\Page\PageContent\PageContentIterator;
use Neunerlei\TinyTimy\DateTimy;
use RecursiveIteratorIterator;

class ContentElementProcessor
{
    use ContainerAwareTrait;
    
    /**
     * @var \LaborDigital\T3sai\Search\Indexer\Page\ContentElement\ContentElementIndexerInterface[]
     */
    protected $elementIndexers = [];
    
    /**
     * Holds the timestamp of the latest/newest content element on the last page that was requested
     * with getContent().
     *
     * @var int
     */
    protected $latestTimestamp = 0;
    
    /**
     * Populates the internal indexer instances with the classes provided to it
     *
     * @param   array  $contentElementIndexers
     *
     * @return void
     */
    public function initializeIndexers(array $contentElementIndexers): void
    {
        $this->elementIndexers = [];
        foreach ($contentElementIndexers as $indexer => $options) {
            $i = $this->makeInstance($indexer);
            
            if (! $i instanceof ContentElementIndexerInterface) {
                continue;
            }
            
            $i->setOptions($options);
            
            $this->elementIndexers[] = $i;
        }
        
        $this->elementIndexers[] = $this->makeInstance(DefaultContentElementIndexer::class);
    }
    
    /**
     * Generates the list of content strings from the list of page contents
     *
     * @param   \LaborDigital\T3sai\Core\Indexer\Node\Node                               $node
     * @param   \LaborDigital\T3sai\Search\Indexer\Page\PageContent\PageContentIterator  $content
     *
     * @return array
     */
    public function generateContent(Node $node, PageContentIterator $content): array
    {
        $this->latestTimestamp = 0;
        
        $contents = [];
        
        foreach (new RecursiveIteratorIterator($content) as $row) {
            $cType = ! empty($row['CType']) ? $row['CType'] : '';
            $listType = ! empty($row['list_type']) ? $row['list_type'] : '';
            
            if ($row['tstamp'] > $this->latestTimestamp) {
                $this->latestTimestamp = $row['tstamp'];
            }
            
            foreach ($this->elementIndexers as $indexer) {
                if (! $indexer->canHandle($cType, $listType, $row, $node)) {
                    continue;
                }
                
                $contents[] = trim($indexer->generateContent($row, $node));
            }
        }
        
        return array_filter($contents);
    }
    
    /**
     * Returns the timestamp of the latest/newest content element on the last page that was requested
     * with getContent().
     *
     * @return \Neunerlei\TinyTimy\DateTimy
     */
    public function getLatestTimestamp(): DateTimy
    {
        return new DateTimy($this->latestTimestamp);
    }
}