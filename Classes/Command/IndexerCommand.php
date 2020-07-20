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

namespace LaborDigital\T3SAI\Command;


use LaborDigital\T3SAI\Controller\Traits\IndexRunnerControllerTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class IndexerCommand extends Command
{
    use IndexRunnerControllerTrait;
    
    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setHelp('Runs the search indexer of the search and index bundle');
    }
    
    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->executeIndexer()) {
            $output->writeln('Done');
        } else {
            $output->writeln('Failed!');
        }
    }
}
