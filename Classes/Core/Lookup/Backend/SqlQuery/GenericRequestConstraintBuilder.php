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
 * Last modified: 2022.04.14 at 15:30
 */

declare(strict_types=1);


namespace LaborDigital\T3sai\Core\Lookup\Backend\SqlQuery;


use LaborDigital\T3sai\Core\Lookup\Request\LookupRequest;
use LaborDigital\T3sai\Domain\Repository\SearchRepository;
use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;

class GenericRequestConstraintBuilder extends AbstractSqlQuery
{
    /**
     * Defines the table for which to build the constraints
     *
     * @var string
     */
    protected $targetTable;
    
    /**
     * The alias to use for the target table
     *
     * @var string|null
     */
    protected $targetTableAlias;
    
    public function __construct(LookupRequest $request, ?string $targetTable = null, ?string $targetTableAlias = null)
    {
        parent::__construct($request);
        $this->targetTable = $targetTable ?? SearchRepository::TABLE_NODES;
        $this->targetTableAlias = $targetTableAlias;
    }
    
    public function build(): string
    {
        $constraints = array_filter(
            [
                $this->buildRequestConstraint(),
                $this->buildRequiredTagConstraint(),
                $this->buildDeniedTagConstraint(),
                $this->buildDeniedWordConstraint(),
            ]
        );
        
        if (empty($constraints)) {
            return '1=1';
        }
        
        return (string)$this->expr()->andX(...$constraints);
    }
    
    protected function buildRequestConstraint(): ?CompositeExpression
    {
        $alias = $this->getTargetTableAlias();
        
        return $this->expr()->andX(
            $this->expr()->eq($alias . 'active', 1),
            $this->expr()->eq($alias . 'site', $this->q($this->request->getSite()->getIdentifier())),
            $this->expr()->eq($alias . 'domain', $this->q($this->request->getDomainIdentifier())),
            $this->expr()->eq($alias . 'lang', $this->q($this->request->getLanguage()->getTwoLetterIsoCode()))
        );
    }
    
    protected function buildRequiredTagConstraint(): ?string
    {
        if (empty($this->input->requiredTags)) {
            return null;
        }
        
        $alias = $this->getTargetTableAlias();
        
        return $this->expr()->in($alias . 'tag', array_map([$this, 'q'], $this->input->requiredTags));
    }
    
    protected function buildDeniedTagConstraint(): ?string
    {
        if (empty($this->input->deniedTags)) {
            return null;
        }
        
        $alias = $this->getTargetTableAlias();
        
        return $this->expr()->notIn($alias . 'tag', array_map([$this, 'q'], $this->input->deniedTags));
    }
    
    protected function buildDeniedWordConstraint(): ?CompositeExpression
    {
        if (empty($this->input->deniedWordLists)) {
            return null;
        }
        
        $isNodeTable = $this->targetTable === SearchRepository::TABLE_NODES;
        $alias = $this->getTargetTableAlias();
        
        $negations = [];
        foreach ($this->input->deniedWordLists as $deniedWordList) {
            if ($isNodeTable) {
                $negations[] = $this->expr()->andX(
                    $this->expr()->neq($alias . 'content', $this->q($deniedWordList)),
                    $this->expr()->notLike($alias . 'content', $this->q('% ' . $deniedWordList)),
                    $this->expr()->notLike($alias . 'content', $this->q($deniedWordList . ' %')),
                    $this->expr()->notLike($alias . 'content', $this->q('% ' . $deniedWordList . ' %')),
                );
                continue;
            }
            
            $negations[] = $this->expr()->andX(
                $this->expr()->neq($alias . 'word', $this->q($deniedWordList)),
                $this->expr()->notLike($alias . 'word', $this->q('%' . $deniedWordList . '%')),
            );
        }
        
        return $this->expr()->andX(...$negations);
    }
    
    protected function getTargetTableAlias(): string
    {
        if (empty($this->targetTableAlias)) {
            return '';
        }
        
        return $this->targetTableAlias . '.';
    }
}