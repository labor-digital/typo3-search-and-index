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
 * Last modified: 2022.04.11 at 19:04
 */

declare(strict_types=1);


namespace LaborDigital\T3sai\Core\Indexer\Queue\Log;


use LaborDigital\T3ba\Core\Di\NoDiInterface;
use LaborDigital\T3sai\Core\Indexer\Node\Node;
use LaborDigital\T3sai\Core\Indexer\Queue\QueueRequest;
use LaborDigital\T3sai\Exception\Indexer\InLimboException;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LogLevel;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

class QueueLogger extends AbstractLogger implements LoggerAwareInterface, NoDiInterface
{
    use LoggerAwareTrait;
    
    protected const ERROR_LEVELS
        = [
            LogLevel::EMERGENCY,
            LogLevel::ALERT,
            LogLevel::CRITICAL,
            LogLevel::ERROR,
        ];
    
    protected $errors = [];
    
    /**
     * @inheritDoc
     */
    public function log($level, $message, array $context = []): void
    {
        $request = $this->formatRequest($context);
        
        if ($this->logger) {
            $this->logger->log($level, $request . ': ' . $message, $context);
        }
        
        if (in_array($level, static::ERROR_LEVELS, true)) {
            $this->errors[] = $request . ': ' . $message;
        }
    }
    
    public function getErrors(): array
    {
        return $this->errors;
    }
    
    protected function formatRequest(array &$context): string
    {
        if (! isset($context['request'])) {
            return 'Unknown request';
        }
        
        $request = $context['request'];
        if (! $request instanceof QueueRequest) {
            return 'Unknown request (present but no QueueRequest)';
        }
        
        unset($context['request']);
        
        $formatted = [];
        
        $site = $this->getRequestValue([$request, 'getSite']);
        if ($site instanceof Site) {
            $formatted[] = 'Site: ' . $site->getIdentifier();
        }
        
        $domainIdentifier = $this->getRequestValue([$request, 'getDomainIdentifier']);
        if (is_string($domainIdentifier)) {
            $formatted[] = 'Search Domain: ' . $domainIdentifier;
        }
        
        $language = $this->getRequestValue([$request, 'getLanguage']);
        if ($language instanceof SiteLanguage) {
            $formatted[] = 'Language: ' . $language->getTwoLetterIsoCode();
        }
        
        $indexer = $this->getRequestValue([$request, 'getRecordIndexer']);
        if ($indexer !== null) {
            $formatted[] = 'Indexer: ' . get_class($indexer);
        }
        
        $node = $this->getRequestValue([$request, 'getNode']);
        if ($node instanceof Node) {
            $formatted[] = rtrim('Node: ' . $node->getGuid() . ' ' . $node->getTitle());
        }
        
        if (empty($formatted)) {
            return 'Request with no configuration';
        }
        
        return 'T3SAI::Request: ' . implode(', ', $formatted);
    }
    
    protected function getRequestValue(callable $caller)
    {
        try {
            return $caller();
        } catch (InLimboException $exception) {
            return null;
        }
    }
}