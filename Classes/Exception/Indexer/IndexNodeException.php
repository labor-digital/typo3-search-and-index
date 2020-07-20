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
 * Last modified: 2020.07.16 at 18:43
 */

declare(strict_types=1);


namespace LaborDigital\T3SAI\Exception\Indexer;


use LaborDigital\T3SAI\Indexer\IndexNode;

class IndexNodeException extends IndexerException
{
    /**
     * @var IndexNode
     */
    protected $node;
    
    public static function makeInstance(IndexNode $node, string $message, ...$args): IndexNodeException
    {
        $self       = new static($message, ...$args);
        $self->node = $node;
        
        return $self;
    }
    
    /**
     * Returns the failed index node
     *
     * @return \LaborDigital\T3SAI\Indexer\IndexNode
     */
    public function getNode(): IndexNode
    {
        return $this->node;
    }
}
