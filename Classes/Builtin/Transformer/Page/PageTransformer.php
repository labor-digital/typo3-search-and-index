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
 * Last modified: 2019.03.22 at 17:49
 */

namespace LaborDigital\T3SAI\Builtin\Transformer\Page;


use LaborDigital\T3SAI\Event\PageIndexNodeFilterEvent;
use LaborDigital\T3SAI\Event\PageListFilterEvent;
use LaborDigital\T3SAI\Indexer\IndexerContext;
use LaborDigital\T3SAI\Indexer\IndexNode;
use LaborDigital\T3SAI\Indexer\Transformer\AbstractRecordTransformer;
use LaborDigital\Typo3BetterApi\Link\TypoLink;
use Neunerlei\Arrays\Arrays;

class PageTransformer extends AbstractRecordTransformer
{
    
    /**
     * @var \LaborDigital\T3SAI\Builtin\Transformer\Page\PageContentGenerator
     */
    protected $contentGenerator;
    
    /**
     * PageDataProvider constructor.
     *
     * @param   \LaborDigital\T3SAI\Builtin\Transformer\Page\PageContentGenerator  $contentGenerator
     */
    public function __construct(PageContentGenerator $contentGenerator)
    {
        $this->contentGenerator = $contentGenerator;
    }
    
    /**
     * @inheritdoc
     */
    public function resolve(IndexerContext $context): iterable
    {
        // Get root pages
        $transformerConfig = $context->getDomainConfig()->getPageTransformerConfig();
        $pages             = $transformerConfig->rootPids;
        if (empty($pages)) {
            $pages = [$context->getTypoContext()->Site()->getCurrent()->getRootPageId()];
        }
        
        // The parsed result of all known pages
        $parsedList = [];
        
        // Already processed uid's -> Prevent duplicates when building the menu
        $knownUids = [];
        
        // Fallback counter -> prevent endless loops
        $c = 0;
        while (! empty($pages) && $c++ < 5000) {
            // Get the first uid from the list of pages
            $uid = (int)reset($pages);
            
            // Remove the first uid from the list of pages
            array_shift($pages);
            
            // Skip if this uid is already known
            if (in_array($uid, $knownUids, true)) {
                continue;
            }
            
            // Add this uid to the known uids
            $knownUids[] = $uid;
            
            // Load the menu from the database
            $menu = $this->Page()
                         ->getPageRepository()
                         ->getMenu($uid, '*', 'sorting',
                             'AND (doktype = 1 OR doktype = 4) AND hidden = 0 AND (sai_index != 1 OR sai_index IS NULL) ' .
                             'AND (fe_group = \'\' OR fe_group = 0)');
            
            // Add this menu entry to the list of known pages
            $parsedList = Arrays::attach($parsedList, $menu);
            
            // Add the list of all uid's to the list of new parents
            $pages = Arrays::attach($pages, Arrays::getPath($menu, '*.uid', []));
        }
        $pages = $parsedList;
        
        // Sort out all links and calculate root line
        $parsedList = [];
        foreach ($pages as $page) {
            if ((int)$page['doktype'] !== 1) {
                continue;
            }
            
            // Calculate root line
            $rl                        = $this->Page()->getRootLine((int)$page['uid']);
            $page['_ROOT_LINE']        = Arrays::getList($rl, ['uid', 'title']);
            $page['_ROOT_LINE_LENGTH'] = count($page['_ROOT_LINE']);
            
            // Use uid as list key
            $parsedList[$page['uid']] = $page;
        }
        $pages = $parsedList;
        
        // Emit hook
        $this->EventBus()->dispatch(($e = new PageListFilterEvent($context, $pages)));
        $pages = $e->getPages();
        
        // Calculate the average root line length
        if (empty($transformerConfig->averageRootLineLength)) {
            $transformerConfig->averageRootLineLength
                = array_reduce($pages, static function ($i, $v) {
                return $i + (int)$v['_ROOT_LINE_LENGTH'];
            }, 0);
            
            if (! empty($pages)) {
                $transformerConfig->averageRootLineLength /= count($pages);
            }
        }
        
        // Done
        return $pages;
    }
    
    /**
     * @inheritdoc
     */
    public function convert($element, IndexNode $node, IndexerContext $context): void
    {
        $transformerConfig = $context->getDomainConfig()->getPageTransformerConfig();
        
        // Add the page title / fallback title
        $title = trim((string)$element[$transformerConfig->titleCol]);
        if (empty($title)) {
            $title = trim((string)$element[$transformerConfig->titleFallbackCol]);
        } else {
            $node->addContent(trim((string)$element[$transformerConfig->titleFallbackCol]), 20);
        }
        $node->setTitle($title);
        
        // Add the other searchable columns
        foreach ($transformerConfig->searchableColumns as $col) {
            if (! isset($element[$col])) {
                continue;
            }
            $node->addContent(trim((string)$element[$col]));
        }
        
        // Add the
        $node->setDescription((string)$element[$transformerConfig->descriptionCol]);
        $node->setTag('page');
        $node->addKeywords((string)$element['sai_keywords']);
        $node->editLink(static function (TypoLink $link) use ($element) {
            return $link->withPid((int)$element['uid']);
        });
        
        // Check if we should put the page on the sitemap
        if (! empty($element['sai_sitemap'])) {
            $node->setAddToSitemap(false);
        }
        
        // Calculate priority
        $rootLineModifier = min(30, -($element['_ROOT_LINE_LENGTH'] - $transformerConfig->averageRootLineLength) * 5);
        $priority         = min(100, ((int)$element['sai_priority']) + $rootLineModifier);
        // Modify the range (settable in the backend) of -100 -> +100 to 0 -> +100
        $priority = $priority + 100 / 2;
        $node->setPriority($priority);
        
        // Resolve the node image
        if (! empty($element[$transformerConfig->imageCol])) {
            $node->setImage($this->FalFiles()->getFile((int)$element['uid'], 'pages', $transformerConfig->imageCol));
        }
        
        // Add the page contents
        $node->addContent($this->contentGenerator->getContent(
            (int)$element['uid'],
            (int)$element['tstamp'],
            $context));
        
        // Set node timestamp
        $node->setTimestamp($this->contentGenerator->getLatestTimestamp());
        
        // Allow filtering
        $this->EventBus()->dispatch(new PageIndexNodeFilterEvent($context));
    }
    
}
