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
 * Last modified: 2022.04.24 at 09:34
 */

declare(strict_types=1);


namespace LaborDigital\T3sai\Api\Util;


use LaborDigital\T3fa\Core\Resource\Repository\Pagination\LateCountingSelfPaginatingInterface;
use LaborDigital\T3fa\Core\Resource\Repository\Pagination\SelfPaginatingInterface;
use LaborDigital\T3sai\Core\Lookup\Paginator\SearchResultPaginator;

class ApiSearchResultPaginator implements SelfPaginatingInterface, LateCountingSelfPaginatingInterface
{
    
    /**
     * @var string
     */
    protected $input;
    
    /**
     * @var array
     */
    protected $options;
    
    /**
     * @var callable
     */
    protected $resultFilter;
    
    /**
     * @var SearchResultPaginator
     */
    protected $concretePaginator;
    
    public function __construct(
        string $input,
        array $options,
        callable $resultFilter
    )
    {
        $this->resultFilter = $resultFilter;
        $this->input = $input;
        $this->options = $options;
    }
    
    /**
     * @inheritDoc
     */
    public function getItemsFor(int $offset, int $limit): iterable
    {
        $page = (int)max(1, ceil(($offset + $limit) / $limit));
        
        $this->concretePaginator = new SearchResultPaginator(
            $this->input, $this->options, $page, $limit
        );
        
        $result = $this->concretePaginator->getPaginatedItems();
        
        return ($this->resultFilter)($result);
    }
    
    /**
     * @inheritDoc
     */
    public function getItemCount(): int
    {
        return $this->concretePaginator->getNumberOfItems();
    }
    
}