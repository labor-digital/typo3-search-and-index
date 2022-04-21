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


namespace LaborDigital\T3sai\Event;


use LaborDigital\T3sai\Core\Indexer\Node\Node;
use LaborDigital\T3sai\Core\Indexer\Queue\QueueRequest;

/**
 * Class PageIndexNodeFilterEvent
 *
 * Is dispatched once for every page node that is transformed using the page indexer
 *
 * @package LaborDigital\T3sai\Event
 */
class PageIndexNodeFilterEvent extends AbstractIndexerEvent
{
    /**
     * @var array
     */
    protected $pageData;
    
    /**
     * @var array
     */
    protected $options;
    
    public function __construct(QueueRequest $request, array $pageData, array $options)
    {
        parent::__construct($request);
        $this->pageData = $pageData;
        $this->options = $options;
    }
    
    /**
     * Returns the index node that is currently being filled
     *
     * @return \LaborDigital\T3sai\Core\Indexer\Node\Node
     */
    public function getNode(): Node
    {
        return $this->request->getNode();
    }
    
    /**
     * Returns the database row that is used to fill the node
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->pageData;
    }
    
    /**
     * Returns the used configuration for the indexer
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }
    
    
}
