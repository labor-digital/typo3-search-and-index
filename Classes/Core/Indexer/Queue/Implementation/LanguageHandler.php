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
 * Last modified: 2022.04.11 at 18:26
 */

declare(strict_types=1);


namespace LaborDigital\T3sai\Core\Indexer\Queue\Implementation;


use InvalidArgumentException;
use LaborDigital\T3sai\Core\Indexer\Queue\QueueHandlerInterface;
use LaborDigital\T3sai\Core\Indexer\Queue\QueueRequest;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

class LanguageHandler implements QueueHandlerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;
    
    /**
     * @inheritDoc
     */
    public function handle($item, QueueRequest $request, callable $children): void
    {
        if (! $item instanceof SiteLanguage) {
            throw new InvalidArgumentException('Item is no instance of: "' . SiteLanguage::class . '"');
        }
        
        $children(
            $request
                ->withLanguage($item)
                ->withSimulationConfig(
                    array_merge(
                        $request->getSimulationConfig(),
                        ['language' => $item]
                    )
                )
        );
    }
    
    /**
     * @inheritDoc
     */
    public function provideIterable(QueueRequest $request): iterable
    {
        if (! $request->getSite()) {
            if ($this->logger) {
                $this->logger->error(
                    'Failed to retrieve site from request',
                    ['request' => $request]
                );
            }
            
            return [];
        }
        
        $allowedLanguages = $request->getDomainConfig()['allowedLanguages'] ?? null;
        
        if ($allowedLanguages === null) {
            return $request->getSite()->getAllLanguages();
        }
        
        $list = [];
        foreach ($request->getSite()->getAllLanguages() as $language) {
            if (in_array($language->getTwoLetterIsoCode(), $allowedLanguages, true)) {
                $list[] = $language;
            }
        }
        
        if (empty($list)) {
            if ($this->logger) {
                $this->logger->error(
                    'Failed to retrieve any valid language for search domain',
                    ['request' => $request]
                );
            }
            
            return [];
        }
        
        return $list;
    }
    
}