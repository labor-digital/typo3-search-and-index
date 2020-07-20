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


namespace LaborDigital\T3SAI\Event;


use LaborDigital\Typo3FrontendApi\JsonApi\Controller\CollectionControllerContext;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class SearchResourceQueryFilterEvent
 *
 * Dispatched in the FrontendApi Search resource controller to filter the incoming search query
 * before it is processed by the search repository
 *
 * @package LaborDigital\T3SAI\Event
 */
class SearchResourceQueryFilterEvent
{
    /**
     * The request which was executed to reach the search endpoint
     *
     * @var \Psr\Http\Message\ServerRequestInterface
     */
    protected $request;
    
    /**
     * The resource controller context for the request
     *
     * @var \LaborDigital\Typo3FrontendApi\JsonApi\Controller\CollectionControllerContext
     */
    protected $context;
    
    /**
     * The list of parsed options of the request
     *
     * @var array
     */
    protected $options;
    
    /**
     * The search query to be executed
     *
     * @var string
     */
    protected $query;
    
    /**
     * SearchResourceQueryFilterEvent constructor.
     *
     * @param   \Psr\Http\Message\ServerRequestInterface                                       $request
     * @param   \LaborDigital\Typo3FrontendApi\JsonApi\Controller\CollectionControllerContext  $context
     * @param   array                                                                          $options
     * @param   string                                                                         $query
     */
    public function __construct(
        ServerRequestInterface $request,
        CollectionControllerContext $context,
        array $options,
        string $query
    ) {
        $this->request = $request;
        $this->context = $context;
        $this->options = $options;
        $this->query   = $query;
    }
    
    /**
     * Returns the request which was executed to reach the search endpoint
     *
     * @return \Psr\Http\Message\ServerRequestInterface
     */
    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }
    
    /**
     * Returns the resource controller context for the request
     *
     * @return \LaborDigital\Typo3FrontendApi\JsonApi\Controller\CollectionControllerContext
     */
    public function getContext(): CollectionControllerContext
    {
        return $this->context;
    }
    
    /**
     * Returns the list of parsed options of the request
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }
    
    /**
     * Allows you to update the list of options before the search is started
     *
     * @param   array  $options
     *
     * @return SearchResourceQueryFilterEvent
     */
    public function setOptions(array $options): SearchResourceQueryFilterEvent
    {
        $this->options = $options;
        
        return $this;
    }
    
    /**
     * Returns the given search query to be executed
     *
     * @return string
     */
    public function getQuery(): string
    {
        return $this->query;
    }
    
    /**
     * Updates the given search query to be executed
     *
     * @param   string  $query
     *
     * @return SearchResourceQueryFilterEvent
     */
    public function setQuery(string $query): SearchResourceQueryFilterEvent
    {
        $this->query = $query;
        
        return $this;
    }
    
    
}
