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
 * Last modified: 2020.07.16 at 18:48
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
 * Last modified: 2019.09.02 at 17:04
 */

namespace LaborDigital\T3SAI\Builtin\Transformer\Page;


use LaborDigital\T3SAI\Builtin\Transformer\Page\ContentElement\ContentElementQueue;
use LaborDigital\T3SAI\Indexer\IndexerContext;
use LaborDigital\Typo3BetterApi\Page\PageService;
use Neunerlei\TinyTimy\DateTimy;
use TYPO3\CMS\Core\SingletonInterface;

class PageContentGenerator implements SingletonInterface
{
    
    /**
     * Holds the timestamp of the latest/newest content element on the last page that was requested
     * with getContent().
     *
     * @var int
     */
    protected $latestTimestamp = 0;
    
    /**
     * @var \LaborDigital\Typo3BetterApi\Page\PageService
     */
    protected $pageService;
    
    /**
     * @var \LaborDigital\T3SAI\Builtin\Transformer\Page\ContentElement\ContentElementQueue
     */
    protected $elementQueue;
    
    /**
     * PageContentGenerator constructor.
     *
     * @param   \LaborDigital\Typo3BetterApi\Page\PageService                                    $pageService
     * @param   \LaborDigital\T3SAI\Builtin\Transformer\Page\ContentElement\ContentElementQueue  $elementQueue
     */
    public function __construct(PageService $pageService, ContentElementQueue $elementQueue)
    {
        $this->pageService  = $pageService;
        $this->elementQueue = $elementQueue;
    }
    
    /**
     * Receives the pid of the page it should extract the contents from as well as the
     * search context to provide, well additional context... it will return a string containing the content's of the
     * tt_content row's that are stored on the page.
     *
     * @param   int                                         $pid
     * @param   int                                         $defaultTimestamp
     * @param   \LaborDigital\T3SAI\Indexer\IndexerContext  $context
     *
     * @return string
     */
    public function getContent(int $pid, int $defaultTimestamp, IndexerContext $context): string
    {
        // Get the layout for the page
        $layout                = $this->pageService->getPageContents($pid, ['language' => $context->getLanguage()]);
        $this->latestTimestamp = $defaultTimestamp;
        
        // Extract the content for all elements
        $content               = [];
        $recursiveLayoutWalker = function (array $layout, callable $recursiveLayoutWalker) use (&$content, $context) {
            // Loop through the columns
            ksort($layout);
            foreach ($layout as $col) {
                foreach ($col as $element) {
                    // Collect the content of the row itself
                    $row       = $element['record'];
                    $content[] = $this->elementQueue->process($row, $context);
                    
                    // Update timestamp
                    if ($row['tstamp'] > $this->latestTimestamp) {
                        $this->latestTimestamp = $row['tstamp'];
                    }
                    
                    // Try to collect content of the child rows
                    if (! empty($element['children'])) {
                        $recursiveLayoutWalker($element['children'], $recursiveLayoutWalker);
                    }
                    
                }
            }
        };
        $recursiveLayoutWalker($layout, $recursiveLayoutWalker);
        
        // Post process the content
        $content = array_map('trim', $content);
        $content = array_filter($content);
        
        // Done
        return implode(' ', $content);
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
