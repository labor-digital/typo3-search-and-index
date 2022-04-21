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
 * Last modified: 2022.04.12 at 17:00
 */

declare(strict_types=1);


namespace LaborDigital\T3sai\Search\Indexer\Page\PageData;


use LaborDigital\T3ba\Core\Di\ContainerAwareTrait;
use LaborDigital\T3ba\Tool\Page\PageService;
use LaborDigital\T3ba\Tool\TypoContext\TypoContext;

class PageDataResolver
{
    use ContainerAwareTrait;
    
    /**
     * @var \LaborDigital\T3ba\Tool\Page\PageService
     */
    protected $pageService;
    
    /**
     * @var \LaborDigital\T3ba\Tool\TypoContext\TypoContext
     */
    protected $typoContext;
    
    public function __construct(PageService $pageService, TypoContext $typoContext)
    {
        $this->pageService = $pageService;
        $this->typoContext = $typoContext;
    }
    
    /**
     * Creates an iterator that returns all valid page elements recursively
     *
     * @param   array|null  $rootPids
     *
     * @return \LaborDigital\T3sai\Search\Indexer\Page\PageData\PageDataIterator
     */
    public function makeDataIterator(?array $rootPids = null): PageDataIterator
    {
        $rootPids = $this->prepareRootPids($rootPids);
        
        return $this->makeInstance(PageDataIterator::class, [$this->prepareRootPids($rootPids), $this]);
    }
    
    /**
     * Handles the resolution of the page data for the configured root page pids
     *
     * @param   array  $rootPids
     * @param   array  $visitedPids
     *
     * @return array
     */
    public function resolveInitialChunk(array $rootPids, array &$visitedPids): array
    {
        $chunk = [];
        foreach ($rootPids as $rootPid) {
            $visitedPids[] = $rootPid;
            $page = $this->pageService->getPageInfo($rootPid);
            
            // Ignore hidden pages
            if ($page['hidden'] === 1) {
                continue;
            }
            
            // Ignore folder root pages and alike
            if ($page['doktype'] !== 1) {
                continue;
            }
            
            // Ignore if disabled
            if ($page['no_search'] === 1) {
                continue;
            }
            
            $chunk[] = $page;
        }
        
        return $chunk;
    }
    
    /**
     * Handles the resolution of the next chunk for the list of pids provided
     *
     * @param   array  $pids
     * @param   array  $visitedPids
     *
     * @return array
     */
    public function resolveSubsequentChunk(array &$pids, array &$visitedPids): array
    {
        $uid = (int)array_shift($pids);
        $knownUidString = implode(', ', $visitedPids);
        $visitedPids[] = $uid;
        
        $menu = $this->pageService
            ->getPageRepository()
            ->getMenu($uid, '*', 'sorting',
                '(no_search != 1 OR no_search IS NULL) ' .
                'AND (fe_group = \'\' OR fe_group = 0) ' .
                (empty($knownUidString) ? '' : 'AND uid NOT IN(' . $knownUidString . ')')
            );
        
        $menuFiltered = [];
        foreach ($menu as $page) {
            // We can't check "hidden" in the getMenu method, because the value
            // might be changed by the overlay logic
            if ($page['hidden'] === 1) {
                continue;
            }
            
            // Ignore if disabled
            if ($page['no_search'] === 1) {
                continue;
            }
            
            $pids[] = $page['uid'];
            
            // Read sub-items of all kinds of pages, but don't actually index them
            if ($page['doktype'] !== 1) {
                continue;
            }
            
            $menuFiltered[] = $page;
        }
        
        return $menuFiltered;
    }
    
    /**
     * Ensures that the root pids exist, are not empty and have the local pid configuration applied
     *
     * @param   array|null  $rootPids
     *
     * @return array
     */
    protected function prepareRootPids(?array $rootPids): array
    {
        if (empty($rootPids)) {
            $rootPids = [$this->typoContext->site()->getCurrent()->getRootPageId()];
        }
        
        return $this->typoContext->pid()->getMultiple($rootPids);
    }
}