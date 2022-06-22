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

namespace LaborDigital\T3sai\Scheduler;

use LaborDigital\T3ba\Core\Di\ContainerAwareTrait;
use LaborDigital\T3ba\ExtConfig\ExtConfigContext;
use LaborDigital\T3ba\ExtConfigHandler\Scheduler\Task\ConfigureTaskInterface;
use LaborDigital\T3ba\ExtConfigHandler\Scheduler\Task\TaskConfigurator;
use LaborDigital\T3sai\Core\Indexer\Indexer;
use LaborDigital\T3sai\Exception\SearchAndIndexException;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

class IndexerTask extends AbstractTask implements ConfigureTaskInterface
{
    use ContainerAwareTrait;
    
    /**
     * @inheritDoc
     */
    public static function configure(TaskConfigurator $taskConfigurator, ExtConfigContext $context): void
    {
        $taskConfigurator->setTitle('t3saibe.indexerTaskTitle')
                         ->setDescription('t3saibe.indexerTaskDesc');
    }
    
    /**
     * @inheritDoc
     */
    public function execute(): bool
    {
        $errors = $this->makeInstance(Indexer::class)->run();
        
        if (! empty($errors)) {
            if ($this->logger) {
                foreach ($errors as $error) {
                    $this->logger->error($error);
                }
            }
            
            if ($this->cs()->typoContext->env()->isDev()) {
                throw new SearchAndIndexException('Failed in indexer: ' . implode(', ', $errors));
            }
        }
        
        return empty($errors);
    }
    
}
