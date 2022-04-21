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
 * Last modified: 2022.04.11 at 14:17
 */

declare(strict_types=1);


namespace LaborDigital\T3sai\Core\Indexer\Queue;


use LaborDigital\T3sai\Core\Indexer\Queue\Log\QueueLogger;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Throwable;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Runner implements LoggerAwareInterface
{
    use LoggerAwareTrait;
    
    /**
     * @var array
     */
    protected $handlers;
    
    public function __construct(array $handlers)
    {
        $this->handlers = $handlers;
    }
    
    /**
     * Dispatches the queue request through all registered handlers
     *
     * @param   \LaborDigital\T3sai\Core\Indexer\Queue\QueueRequest  $request
     *
     * @return array|null
     */
    public function run(QueueRequest $request): ?array
    {
        $logger = $this->initializeLogger();
        
        if (empty($this->handlers)) {
            return null;
        }
        
        $this->runNextHandler($request, $this->handlers);
        
        return empty($logger->getErrors()) ? null : $logger->getErrors();
    }
    
    /**
     * Initializes our internal logger implementation that keeps track of errors that occur along the way
     *
     * @return \LaborDigital\T3sai\Core\Indexer\Queue\Log\QueueLogger
     */
    protected function initializeLogger(): QueueLogger
    {
        $currentLogger = $this->logger;
        if ($currentLogger instanceof QueueLogger) {
            return $currentLogger;
        }
        
        $this->logger = GeneralUtility::makeInstance(QueueLogger::class);
        if ($currentLogger) {
            $this->logger->setLogger($currentLogger);
        }
        
        return $this->logger;
    }
    
    /**
     * Shifts the next handler from the $handlers stack and executes it.
     *
     * @param   \LaborDigital\T3sai\Core\Indexer\Queue\QueueRequest  $request
     * @param   array                                                $handlers
     *
     * @return void
     */
    protected function runNextHandler(QueueRequest $request, array $handlers): void
    {
        $handler = array_shift($handlers);
        
        if (! $handler instanceof QueueHandlerInterface) {
            if ($this->logger) {
                $this->logger->critical(
                    'Indexer failed! Handler class: "' .
                    get_class($handler) . '" does not implement: "' . QueueHandlerInterface::class . '"',
                    ['request' => $request]);
            }
            
            return;
        }
        
        if ($handler instanceof LoggerAwareInterface) {
            $handler->setLogger($this->logger);
        }
        
        try {
            foreach ($handler->provideIterable($request) as $item) {
                try {
                    $handler->handle($item, $request, function (QueueRequest $request) use ($handlers) {
                        $this->runNextHandler($request, $handlers);
                    });
                } catch (Throwable $e) {
                    if ($this->logger) {
                        $this->logger->critical(
                            $e->getMessage(),
                            ['exception' => $e, 'request' => $request]
                        );
                    }
                }
            }
            
            if ($handler instanceof PostProcessingQueueHandlerInterface) {
                $handler->postProcess($request);
            }
        } catch (Throwable $e) {
            if ($this->logger) {
                $this->logger->critical(
                    $e->getMessage(),
                    ['exception' => $e, 'request' => $request]
                );
            }
        }
    }
}