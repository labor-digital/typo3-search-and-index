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


namespace LaborDigital\T3sai\Search\Indexer\Page\PageContent;


use LaborDigital\T3ba\Core\Di\ContainerAwareTrait;
use LaborDigital\T3ba\Tool\Page\PageService;

class PageContentResolver
{
    use ContainerAwareTrait;
    
    /**
     * @var \LaborDigital\T3ba\Tool\Page\PageService
     */
    protected $pageService;
    
    public function __construct(PageService $pageService)
    {
        $this->pageService = $pageService;
    }
    
    /**
     * Creates a new recursive iterator that allows you to traverse the contents of a
     * page in a continuous loop.
     *
     * @param   int  $pid
     *
     * @return \LaborDigital\T3sai\Search\Indexer\Page\PageContent\PageContentIterator
     */
    public function makeContentIterator(int $pid): PageContentIterator
    {
        $data = $this->pageService->getPageContents($pid, ['includeExtensionFields']);
        
        return $this->makeInstance(PageContentIterator::class, [$data, true]);
    }
}