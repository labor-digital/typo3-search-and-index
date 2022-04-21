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
 * Last modified: 2022.04.20 at 18:02
 */

declare(strict_types=1);


namespace LaborDigital\T3sai\Core\Lookup\Paginator;


use LaborDigital\T3ba\Core\Di\ContainerAwareTrait;
use LaborDigital\T3ba\Core\Di\NoDiInterface;
use LaborDigital\T3sai\Domain\Repository\SearchRepository;
use TYPO3\CMS\Core\Pagination\AbstractPaginator;

class SearchResultPaginator extends AbstractPaginator implements NoDiInterface
{
    use ContainerAwareTrait;
    
    /**
     * @var string
     */
    protected $input;
    
    /**
     * @var array
     */
    protected $options;
    
    /**
     * @var \LaborDigital\T3sai\Domain\Repository\SearchRepository
     */
    protected $repository;
    
    protected $items = [];
    
    protected $totalItems;
    
    /**
     * Creates a new search result paginator
     *
     * @param   string      $input              The input string provided by the user
     * @param   array|null  $options            The options to use when the search is executed
     *                                          {@see SearchRepository::findSearchResults() for a list of valid options}
     *                                          NOTE: 'maxItems', 'offset' and 'maxTagItems' are ignored by the paginator
     * @param   int|null    $currentPageNumber  The current page number to display
     * @param   int|null    $itemsPerPage       The number of items to show per page
     */
    public function __construct(
        string $input,
        ?array $options = null,
        ?int $currentPageNumber = null,
        ?int $itemsPerPage = null
    )
    {
        $this->input = $input;
        
        $options = $options ?? [];
        unset($options['maxItems'], $options['offset'], $options['maxTagItems']);
        $this->options = $options;
        
        $this->setCurrentPageNumber($currentPageNumber ?? 1);
        $this->setItemsPerPage($itemsPerPage ?? 10);
        
        $this->updateInternalState();
    }
    
    /**
     * @inheritDoc
     */
    protected function updatePaginatedItems(int $itemsPerPage, int $offset): void
    {
        $options = $this->options;
        $options['maxItems'] = $itemsPerPage;
        $options['offset'] = $offset;
        
        $this->items = $this->makeInstance(SearchRepository::class)->findSearchResults($this->input, $options);
        $this->totalItems = null;
    }
    
    /**
     * @inheritDoc
     */
    protected function getTotalAmountOfItems(): int
    {
        return $this->totalItems ??
               ($this->totalItems
                   = $this->makeInstance(SearchRepository::class)
                          ->findSearchCounts($this->input, $this->options)['_total'] ?? 0);
    }
    
    /**
     * @inheritDoc
     */
    protected function getAmountOfItemsOnCurrentPage(): int
    {
        return count($this->items);
    }
    
    /**
     * @inheritDoc
     */
    public function getPaginatedItems(): iterable
    {
        return $this->items;
    }
    
}