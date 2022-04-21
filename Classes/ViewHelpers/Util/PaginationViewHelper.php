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
 * Last modified: 2022.04.20 at 19:26
 */

declare(strict_types=1);


namespace LaborDigital\T3sai\ViewHelpers\Util;


use LaborDigital\T3ba\Tool\Link\LinkService;
use LaborDigital\T3ba\Tool\Translation\Translator;
use LaborDigital\T3sai\Controller\SearchController;
use TYPO3\CMS\Core\Pagination\AbstractPaginator;
use TYPO3\CMS\Core\Pagination\SimplePagination;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class PaginationViewHelper extends AbstractViewHelper
{
    /**
     * @var \LaborDigital\T3ba\Tool\Link\LinkService
     */
    protected $linkService;
    
    /**
     * @var \LaborDigital\T3ba\Tool\Translation\Translator
     */
    protected $translator;
    
    protected $escapeOutput = false;
    
    public function __construct(LinkService $linkService, Translator $translator)
    {
        $this->linkService = $linkService;
        $this->translator = $translator;
    }
    
    /**
     * @inheritDoc
     */
    public function initializeArguments(): void
    {
        $this->registerArgument('paginator', 'object', 'The paginator to use when generating the links', true);
    }
    
    public function render(): string
    {
        $paginator = $this->arguments['paginator'];
        if ($paginator instanceof AbstractPaginator) {
            $paginator = new SimplePagination($paginator);
        }
        
        if (! $paginator instanceof SimplePagination) {
            throw new \InvalidArgumentException('The given paginator is invalid!');
        }
        
        $output = [];
        $link = $this->linkService->getLink()
                                  ->withControllerClass(SearchController::class)
                                  ->withControllerAction('list')
                                  ->withKeepQuery(true);
        
        if ($paginator->getPreviousPageNumber() !== null) {
            $href = $link->withAddedToArgs('page', $paginator->getPreviousPageNumber())
                         ->build(['relative']);
            $output[] = '<a href="' . $href . '">' .
                        $this->translator->translate('t3sai.pagination.previous') .
                        '</a>';
        }
        
        if ($paginator->getNextPageNumber() !== null) {
            if (! empty($output)) {
                $output[] = ' | ';
            }
            
            $href = $link->withAddedToArgs('page', $paginator->getNextPageNumber())
                         ->build(['relative']);
            
            $output[] = '<a href="' . $href . '">' .
                        $this->translator->translate('t3sai.pagination.next') .
                        '</a>';
        }
        
        return implode(PHP_EOL, $output);
    }
    
}