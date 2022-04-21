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
 * Last modified: 2022.04.11 at 15:25
 */

declare(strict_types=1);


namespace LaborDigital\T3sai\Core\Indexer\Queue\Implementation;


use InvalidArgumentException;
use LaborDigital\T3ba\Tool\TypoContext\TypoContext;
use LaborDigital\T3sai\Core\Indexer\Queue\QueueHandlerInterface;
use LaborDigital\T3sai\Core\Indexer\Queue\QueueRequest;
use TYPO3\CMS\Core\Site\Entity\Site;

class SiteHandler implements QueueHandlerInterface
{
    /**
     * @var \LaborDigital\T3ba\Tool\TypoContext\TypoContext
     */
    protected $typoContext;
    
    public function __construct(TypoContext $typoContext)
    {
        $this->typoContext = $typoContext;
    }
    
    /**
     * @inheritDoc
     */
    public function handle($item, QueueRequest $request, callable $children): void
    {
        if (! $item instanceof Site) {
            throw new InvalidArgumentException('Item is no instance of: "' . Site::class . '"');
        }
        
        $children(
            $request
                ->withDomainConfig($request->getDomainConfig()[$item->getIdentifier()] ?? [])
                ->withSite($item)
                ->withSimulationConfig(
                    array_merge(
                        $request->getSimulationConfig(),
                        ['site' => $item->getIdentifier()]
                    )
                )
        );
    }
    
    /**
     * @inheritDoc
     */
    public function provideIterable(QueueRequest $request): iterable
    {
        return $this->typoContext->site()->getAll();
    }
    
}