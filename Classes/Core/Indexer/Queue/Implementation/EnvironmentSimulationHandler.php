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
 * Last modified: 2022.04.11 at 19:50
 */

declare(strict_types=1);


namespace LaborDigital\T3sai\Core\Indexer\Queue\Implementation;


use LaborDigital\T3ba\Tool\Simulation\EnvironmentSimulator;
use LaborDigital\T3sai\Core\Indexer\Queue\QueueHandlerInterface;
use LaborDigital\T3sai\Core\Indexer\Queue\QueueRequest;
use LaborDigital\T3sai\Exception\Indexer\IndexerException;
use TYPO3\CMS\Core\Http\ImmediateResponseException;

class EnvironmentSimulationHandler implements QueueHandlerInterface
{
    /**
     * @var \LaborDigital\T3ba\Tool\Simulation\EnvironmentSimulator
     */
    protected $simulator;
    
    public function __construct(EnvironmentSimulator $simulator)
    {
        $this->simulator = $simulator;
    }
    
    /**
     * @inheritDoc
     * @throws \LaborDigital\T3sai\Exception\Indexer\IndexerException
     */
    public function handle($item, QueueRequest $request, callable $children): void
    {
        $config = $request->getSimulationConfig();
        
        if (empty($config)) {
            $children($request);
            
            return;
        }
        
        try {
            $this->simulator->runWithEnvironment($config, function () use ($children, $request) {
                $children($request);
            });
        } catch (ImmediateResponseException $e) {
            throw new IndexerException('Failed to run environment simulation', 0, $e);
        }
    }
    
    /**
     * @inheritDoc
     */
    public function provideIterable(QueueRequest $request): iterable
    {
        // Null will be simply ignored but forces handle to be called once
        return [null];
    }
    
}