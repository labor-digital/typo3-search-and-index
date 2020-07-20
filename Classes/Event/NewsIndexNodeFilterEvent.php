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


use LaborDigital\T3SAI\Configuration\NewsDataTransformerConfig;
use LaborDigital\T3SAI\Indexer\IndexerContext;
use LaborDigital\T3SAI\Indexer\IndexNode;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Class NewsIndexNodeFilterEvent
 *
 * Is dispatched once for every page node that is transformed using the news transformer
 *
 * @package LaborDigital\T3SAI\Event
 */
class NewsIndexNodeFilterEvent
{
    /**
     * The index node that is currently being filled
     *
     * @var \LaborDigital\T3SAI\Indexer\IndexNode
     */
    protected $node;
    
    /**
     * The entity that is used to fill the node
     *
     * @var \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
     */
    protected $entity;
    
    /**
     * The used generator context
     *
     * @var \LaborDigital\T3SAI\Indexer\IndexerContext
     */
    protected $context;
    
    /**
     * The used configuration for the transformer
     *
     * @var \LaborDigital\T3SAI\Configuration\NewsDataTransformerConfig
     */
    protected $config;
    
    /**
     * NewsIndexNodeFilterEvent constructor.
     *
     * @param   \LaborDigital\T3SAI\Indexer\IndexNode                        $node
     * @param   \TYPO3\CMS\Extbase\DomainObject\AbstractEntity               $entity
     * @param   \LaborDigital\T3SAI\Indexer\IndexerContext                   $context
     * @param   \LaborDigital\T3SAI\Configuration\NewsDataTransformerConfig  $config
     */
    public function __construct(IndexNode $node, AbstractEntity $entity, IndexerContext $context, NewsDataTransformerConfig $config)
    {
        $this->node    = $node;
        $this->entity  = $entity;
        $this->context = $context;
        $this->config  = $config;
    }
    
    /**
     * Returns the index node that is currently being filled
     *
     * @return \LaborDigital\T3SAI\Indexer\IndexNode
     */
    public function getNode(): IndexNode
    {
        return $this->node;
    }
    
    /**
     * Returns the entity that is used to fill the node
     *
     * @return \TYPO3\CMS\Extbase\DomainObject\AbstractEntity|\GeorgRinger\News\Domain\Model\News
     */
    public function getEntity(): AbstractEntity
    {
        return $this->entity;
    }
    
    /**
     * Returns the used generator context
     *
     * @return \LaborDigital\T3SAI\Indexer\IndexerContext
     */
    public function getContext(): IndexerContext
    {
        return $this->context;
    }
    
    /**
     * Returns the used configuration for the transformer
     *
     * @return \LaborDigital\T3SAI\Configuration\NewsDataTransformerConfig
     */
    public function getConfig(): NewsDataTransformerConfig
    {
        return $this->config;
    }
    
    
}
