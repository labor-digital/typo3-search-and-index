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
 * Last modified: 2022.04.11 at 18:44
 */

declare(strict_types=1);


namespace LaborDigital\T3sai\Core\Indexer\Queue\Implementation;


use InvalidArgumentException;
use LaborDigital\T3ba\Core\Di\ContainerAwareTrait;
use LaborDigital\T3sai\Core\Indexer\Queue\QueueHandlerInterface;
use LaborDigital\T3sai\Core\Indexer\Queue\QueueRequest;
use LaborDigital\T3sai\Search\Indexer\RecordIndexerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class RecordIndexerHandler implements QueueHandlerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;
    use ContainerAwareTrait;
    
    /**
     * @inheritDoc
     */
    public function handle($item, QueueRequest $request, callable $children): void
    {
        if (! is_array($item)) {
            throw new InvalidArgumentException('Item is expected to be an array of $className and $options');
        }
        
        [$className, $options] = $item;
        
        $indexer = $this->makeInstance($className);
        
        if (! $indexer instanceof RecordIndexerInterface) {
            throw new InvalidArgumentException('Invalid indexer class: "' . $className . '" provided!');
        }
        
        $indexer->setOptions($options);
        
        $children($request->withRecordIndexer($indexer));
    }
    
    /**
     * @inheritDoc
     */
    public function provideIterable(QueueRequest $request): iterable
    {
        $recordIndexers = $request->getDomainConfig()['indexer']['record'] ?? [];
        if (empty($recordIndexers)) {
            if ($this->logger) {
                $this->logger->error(
                    'There are no registered indexers',
                    ['request' => $request]
                );
            }
            
            return [];
        }
        
        $list = [];
        foreach ($recordIndexers as $className => $options) {
            $list[] = [$className, $options];
        }
        
        return $list;
    }
    
}