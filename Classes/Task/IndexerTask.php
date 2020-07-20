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

namespace LaborDigital\T3SAI\Task;

use LaborDigital\T3SAI\Exception\SearchAndIndexException;
use LaborDigital\T3SAI\Indexer\Indexer;
use LaborDigital\Typo3BetterApi\Container\ContainerAwareTrait;
use LaborDigital\Typo3BetterApi\TypoContext\TypoContext;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

class IndexerTask extends AbstractTask
{
    use ContainerAwareTrait;
    
    /**
     * @inheritDoc
     */
    public function execute(): bool
    {
        $indexer = $this->getInstanceOf(Indexer::class);
        $indexer->run();
        if ($indexer->hasErrors()) {
            if ($this->getInstanceOf(TypoContext::class)->Env()->isDev()) {
                $errors = $indexer->getErrors();
                throw new SearchAndIndexException(reset($errors));
            }
        }
        
        return ! $indexer->hasErrors();
    }
    
}
