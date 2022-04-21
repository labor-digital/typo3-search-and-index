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

namespace LaborDigital\T3sai\Command;


use LaborDigital\T3ba\Core\Di\ContainerAwareTrait;
use LaborDigital\T3ba\ExtConfigHandler\Command\ConfigureCliCommandInterface;
use LaborDigital\T3sai\Core\Indexer\Indexer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class IndexerCommand extends Command implements ConfigureCliCommandInterface
{
    use ContainerAwareTrait;
    
    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setDescription('Runs the search indexer of the T3SAI extension');
    }
    
    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Starting T3SAI Indexer, this might take a while...');
        
        $errors = $this->makeInstance(Indexer::class)->run();
        
        if (! $errors) {
            $output->writeln('OK - T3SAI indexer finished without problems');
            
            return 0;
        }
        
        $output->writeln('ERRORS - There were errors while executing the T3SAI indexer...');
        foreach ($errors as $error) {
            $output->writeln('  - ' . $error);
        }
        
        return 1;
    }
}
