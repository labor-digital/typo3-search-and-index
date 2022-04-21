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
 * Last modified: 2022.04.14 at 17:04
 */

declare(strict_types=1);


namespace LaborDigital\T3sai\Core\Lookup\Backend\SqlQuery;

use LaborDigital\T3sai\Core\Lookup\Request\LookupRequest;
use LaborDigital\T3sai\Domain\Repository\SearchRepository;
use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

abstract class AbstractSqlQuery implements SqlQueryInterface
{
    protected const TABLE_NODES = SearchRepository::TABLE_NODES;
    protected const TABLE_WORDS = SearchRepository::TABLE_WORDS;
    
    /**
     * @var \LaborDigital\T3sai\Core\Lookup\Request\LookupRequest
     */
    protected $request;
    
    /**
     * @var \TYPO3\CMS\Core\Database\Query\QueryBuilder
     */
    protected $qb;
    
    /**
     * @var \TYPO3\CMS\Core\Database\Connection
     */
    protected $connection;
    
    /**
     * @var \LaborDigital\T3sai\Core\Lookup\Lexer\ParsedInput
     */
    protected $input;
    
    public function __construct(LookupRequest $request)
    {
        $this->request = $request;
        $this->qb = $request->getQueryBuilder();
        $this->connection = $this->qb->getConnection();
        $this->input = $request->getInput();
    }
    
    /**
     * Shortcut for the doctrine expression build
     *
     * @return \TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder
     */
    protected function expr(): ExpressionBuilder
    {
        return $this->qb->expr();
    }
    
    /**
     * Shortcut to quote a value
     *
     * @param $value
     *
     * @return string
     */
    protected function q($value): string
    {
        return $this->connection->quote($value);
    }
    
    /**
     * Shortcut to quote a single database identifier
     *
     * @param   string  $identifier
     *
     * @return string
     */
    protected function qi(string $identifier): string
    {
        return $this->connection->quoteIdentifier($identifier);
    }
    
    /**
     * Wraps the given query with the requirements provided by the current request object
     *
     * @param   CompositeExpression|string  $query
     * @param   string|null                 $targetTable
     * @param   string|null                 $targetTableAlias
     * @param   AbstractSqlQuery|null       $constraintBuilder
     *
     * @return \TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression
     */
    protected function wrapQueryWithRequestConstraints(
        $query,
        ?string $targetTable = null,
        ?string $targetTableAlias = null,
        ?AbstractSqlQuery $constraintBuilder = null
    ): CompositeExpression
    {
        if (! $constraintBuilder) {
            /** @noinspection CallableParameterUseCaseInTypeContextInspection */
            $constraintBuilder = GeneralUtility::makeInstance(GenericRequestConstraintBuilder::class, $this->request, $targetTable, $targetTableAlias);
        }
        
        return $this->expr()->andX(
            $query,
            $constraintBuilder->build()
        );
    }
    
    /**
     * @inheritdoc
     */
    public function __toString(): string
    {
        return $this->build();
    }
}