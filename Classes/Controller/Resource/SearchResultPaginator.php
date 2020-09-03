<?php
/*
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
 * Last modified: 2020.09.03 at 12:24
 */

declare(strict_types=1);


namespace LaborDigital\T3SAI\Controller\Resource;


use LaborDigital\T3SAI\Domain\Repository\SearchRepository;
use LaborDigital\Typo3FrontendApi\JsonApi\Pagination\SelfPaginatingInterface;

class SearchResultPaginator implements SelfPaginatingInterface
{
    /**
     * @var string
     */
    protected $queryString;

    /**
     * @var array
     */
    protected $options;

    /**
     * @var \LaborDigital\T3SAI\Domain\Repository\SearchRepository
     */
    protected $repository;

    /**
     * @var callable
     */
    protected $resultFilter;

    /**
     * SearchResultPaginator constructor.
     *
     * @param   string                                                  $queryString
     * @param   array                                                   $options
     * @param   \LaborDigital\T3SAI\Domain\Repository\SearchRepository  $repository
     * @param   callable                                                $resultFilter
     */
    public function __construct(string $queryString, array $options, SearchRepository $repository, callable $resultFilter)
    {
        $this->queryString  = $queryString;
        $this->options      = $options;
        $this->repository   = $repository;
        $this->resultFilter = $resultFilter;
    }

    /**
     * @inheritDoc
     */
    public function getItemsFor(int $offset, int $limit): iterable
    {
        $options             = $this->options;
        $options['maxItems'] = $limit;
        $options['offset']   = $offset;
        $result              = $this->repository->findByQuery($this->queryString, $options);

        return ($this->resultFilter)($result);
    }

    /**
     * @inheritDoc
     */
    public function getItemCount(): int
    {
        return $this->repository->getCountForQuery($this->queryString, $this->options)['@total'];
    }

}
