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

namespace LaborDigital\T3sai\Domain\Repository;

use LaborDigital\T3sai\Core\Lookup\Backend\Processor\LookupResultProcessorInterface;
use LaborDigital\T3sai\Core\Lookup\Backend\Processor\NullProcessor;
use LaborDigital\T3sai\Core\Lookup\Backend\SqlQuery\SqlQueryProviderInterface;
use LaborDigital\T3sai\Core\Lookup\Lexer\InputLexer;
use LaborDigital\T3sai\Core\Lookup\Request\LookupRequest;
use LaborDigital\T3sai\Core\Lookup\Request\RequestFactory;
use LaborDigital\T3sai\Event\LookupResultFilterEvent;
use LaborDigital\T3sai\Exception\MissingSqlQueryProviderException;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\SingletonInterface;

class SearchRepository implements SingletonInterface
{
    public const TABLE_NODES = 'tx_search_and_index_nodes';
    public const TABLE_WORDS = 'tx_search_and_index_words';
    
    /**
     * @var \LaborDigital\T3sai\Core\Lookup\Backend\SqlQuery\SqlQueryProviderInterface[]
     */
    protected $sqlQueryProviders = [];
    
    /**
     * @var LookupResultProcessorInterface[]
     */
    protected $resultProcessors = [];
    
    /**
     * @var \LaborDigital\T3sai\Core\Lookup\Lexer\InputLexer
     */
    protected $lexer;
    
    /**
     * @var \LaborDigital\T3sai\Core\Lookup\Request\RequestFactory
     */
    protected $requestFactory;
    
    /**
     * @var \Psr\EventDispatcher\EventDispatcherInterface
     */
    protected $eventDispatcher;
    
    public function __construct(
        InputLexer $lexer,
        RequestFactory $requestFactory,
        EventDispatcherInterface $eventDispatcher
    )
    {
        $this->requestFactory = $requestFactory;
        $this->eventDispatcher = $eventDispatcher;
    }
    
    /**
     * Registers a new implementation for the sql query provider
     *
     * @param   \LaborDigital\T3sai\Core\Lookup\Backend\SqlQuery\SqlQueryProviderInterface
     *
     * @return $this
     */
    public function addSqlQueryProvider(SqlQueryProviderInterface $provider): self
    {
        $this->sqlQueryProviders[] = $provider;
        
        return $this;
    }
    
    /**
     * Registers a new result processor to post-process the rows resolved from the database
     *
     * @param   \LaborDigital\T3sai\Core\Lookup\Backend\Processor\LookupResultProcessorInterface  $processor
     *
     * @return $this
     */
    public function addResultProcessor(LookupResultProcessorInterface $processor): self
    {
        $this->resultProcessors[] = $processor;
        
        return $this;
    }
    
    /**
     * Returns a list of all currently stored tags in the database.
     * The tags are provided as associative array with their display and input labels
     *
     * @param   array  $options   Additional options to use when looking up the tags
     *                            - domain string: By default, the best matching search domain (selected by site and language) will be used,
     *                            you can use this option to force the search to use a specific domain with the given identifier instead.
     *                            - site string|SiteInterface: By default, the current TYPO3 site will be used for the search.
     *                            This option can be used to override the defaults to a specific site.
     *                            - langauge string|int|SiteLanguage: By default, the current TYPO3 frontend language will be used for the search.
     *                            This option can be used to override the defaults to a specific language.
     *
     * @return array
     */
    public function findAllTags(array $options = []): array
    {
        return $this->dispatchRequest(LookupRequest::TYPE_TAGS, $options);
    }
    
    /**
     * Returns a list of search results for the given input
     *
     * @param   string  $input    The input string to find the results for
     * @param   array   $options  Additional options to use when looking up the results
     *                            - maxItems int: Sets the maximal number of search results returned by this method.
     *                            By default, all search results will be returned only limited by "maxTagItems".
     *                            WARNING: If "maxTagItems" is set, this setting will be ignored
     *                            - offset int: Sets the offset from the top of the result list to return.
     *                            WARNING: If "maxTagItems" is set, this setting will be ignored
     *                            - maxTagItems int: Sets the maximal number of search results returned for a single tag.
     *                            WARNING: This overrides "maxItems"
     *                            - additionalWhere string: Additional "WHERE" condition which limits the number of outputted results.
     *                            MUST start with an OR, or an AND. All fields MUST be resolved through the `r`.`field` alias.
     *                            Your code MUST be sanitized!
     *                            - contentMatchLength int (400): The max number of characters the contentMatch result field should contain
     *                            - domain string: By default, the best matching search domain (selected by site and language) will be used,
     *                            you can use this option to force the search to use a specific domain with the given identifier instead.
     *                            - site string|SiteInterface: By default, the current TYPO3 site will be used for the search.
     *                            This option can be used to override the defaults to a specific site.
     *                            - langauge string|int|SiteLanguage: By default, the current TYPO3 frontend language will be used for the search.
     *                            This option can be used to override the defaults to a specific language.
     *
     * @return array
     */
    public function findSearchResults(string $input, array $options = []): array
    {
        return $this->dispatchRequest(LookupRequest::TYPE_SEARCH, $options, $input);
    }
    
    /**
     * Returns an array containing the count of results found by the respective tags.
     * The array also contains an "_total" key, which holds the number of all results that were found
     *
     * @param   string  $input    The input string to find the counts for
     * @param   array   $options  Additional options to use when calculating the count
     *                            WARNING: This overrides "maxItems"
     *                            - additionalWhere string: Additional "WHERE" condition which limits the number of outputted results.
     *                            MUST start with an OR, or an AND. All fields MUST be resolved through the `r`.`field` alias.
     *                            Your code MUST be sanitized!
     *                            - domain string: By default, the best matching search domain (selected by site and language) will be used,
     *                            you can use this option to force the search to use a specific domain with the given identifier instead.
     *                            - site string|SiteInterface: By default, the current TYPO3 site will be used for the search.
     *                            This option can be used to override the defaults to a specific site.
     *                            - langauge string|int|SiteLanguage: By default, the current TYPO3 frontend language will be used for the search.
     *                            This option can be used to override the defaults to a specific language.
     *
     * @return array
     */
    public function findSearchCounts(string $input, array $options = []): array
    {
        return $this->dispatchRequest(LookupRequest::TYPE_SEARCH_COUNT, $options, $input);
    }
    
    /**
     * Resolves a list of autocomplete suggestions based on the given input.
     * If the last char of the input is a space (" ") the suggestions for the next word will be returned,
     * If the last char of the input is a normal character, the suggestions to complete the current word will be returned.
     *
     * @param   string  $input    The input to find the autocomplete results for
     * @param   array   $options  Additional options to use while finding the results
     *                            - maxItems int: Sets the maximal number of search results returned by this method.
     *                            - offset int: Sets the offset from the top of the result list to return.
     *                            - domain string: By default, the best matching search domain (selected by site and language) will be used,
     *                            you can use this option to force the search to use a specific domain with the given identifier instead.
     *                            - site string|SiteInterface: By default, the current TYPO3 site will be used for the search.
     *                            This option can be used to override the defaults to a specific site.
     *                            - langauge string|int|SiteLanguage: By default, the current TYPO3 frontend language will be used for the search.
     *                            This option can be used to override the defaults to a specific language.
     *
     * @return array
     */
    public function findAutocompleteResults(string $input, array $options = []): array
    {
        return $this->dispatchRequest(LookupRequest::TYPE_AUTOCOMPLETE, $options, $input);
    }
    
    /**
     * Internal helper to dispatch the lifecycle of a lookup request
     *
     * @param   string       $requestType
     * @param   array        $options
     * @param   string|null  $input
     *
     * @return array
     */
    protected function dispatchRequest(string $requestType, array $options = [], ?string $input = null): array
    {
        $request = $this->makeRequest($requestType, $options, $input);
        
        return $this->eventDispatcher->dispatch(
            new LookupResultFilterEvent(
                $request,
                $this->findProcessor($request)->process(
                    $request->getQueryBuilder()->getConnection()->executeQuery(
                        (string)$this->findProvider($request)->getQuery($request)
                    ),
                    $request
                )
            )
        )->getResult();
    }
    
    /**
     * Tries to resolve the correct sql query provider for the current request,
     * or will die horribly throwing an exception
     *
     * @param   \LaborDigital\T3sai\Core\Lookup\Request\LookupRequest  $request
     *
     * @return \LaborDigital\T3sai\Core\Lookup\Backend\SqlQuery\SqlQueryProviderInterface
     * @throws \LaborDigital\T3sai\Exception\MissingSqlQueryProviderException
     */
    protected function findProvider(LookupRequest $request): SqlQueryProviderInterface
    {
        $connection = $request->getQueryBuilder()->getConnection();
        $platformName = $connection->getDriver()->getDatabasePlatform()->getName();
        
        foreach ($this->sqlQueryProviders as $provider) {
            if ($provider->canHandle($platformName, $connection, $request)) {
                return $provider;
            }
        }
        
        throw new MissingSqlQueryProviderException(
            'There is no available sql query providers available for database platform: "' . $platformName . '"');
    }
    
    /**
     * Tries to resolve a matching result processor for the given request.
     * If no processor can be found the null processor will be returned
     *
     * @param   \LaborDigital\T3sai\Core\Lookup\Request\LookupRequest  $request
     *
     * @return \LaborDigital\T3sai\Core\Lookup\Backend\Processor\LookupResultProcessorInterface
     */
    protected function findProcessor(LookupRequest $request): LookupResultProcessorInterface
    {
        foreach ($this->resultProcessors as $processor) {
            if ($processor->canHandle($request)) {
                return $processor;
            }
        }
        
        return new NullProcessor();
    }
    
    /**
     * Utilizes the lexer and request factory to generate a new request instance based on the given input
     *
     * @param   string       $requestType
     * @param   array        $options
     * @param   string|null  $input
     *
     * @return \LaborDigital\T3sai\Core\Lookup\Request\LookupRequest
     */
    protected function makeRequest(string $requestType, array $options = [], ?string $input = null): LookupRequest
    {
        return $this->requestFactory->makeRequest(
            $input,
            $requestType,
            $options
        );
    }
}
