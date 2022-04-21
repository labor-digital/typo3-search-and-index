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
 * Last modified: 2022.04.20 at 12:19
 */

declare(strict_types=1);


namespace LaborDigital\T3sai\EventHandler;


use LaborDigital\T3sai\Core\Sitemap\SitemapGenerator;
use LaborDigital\T3sai\Event\IndexerBeforeNodePersistationEvent;
use Neunerlei\EventBus\Subscription\EventSubscriptionInterface;
use Neunerlei\EventBus\Subscription\LazyEventSubscriberInterface;

class SitemapGeneratorEventHandler implements LazyEventSubscriberInterface
{
    /**
     * @var \LaborDigital\T3sai\Core\Sitemap\SitemapGenerator
     */
    protected $sitemapGenerator;
    
    public function __construct(SitemapGenerator $sitemapGenerator)
    {
        $this->sitemapGenerator = $sitemapGenerator;
    }
    
    /**
     * @inheritDoc
     */
    public static function subscribeToEvents(EventSubscriptionInterface $subscription): void
    {
        $subscription->subscribe(IndexerBeforeNodePersistationEvent::class, 'onNodePersistation');
    }
    
    public function onNodePersistation(IndexerBeforeNodePersistationEvent $e): void
    {
        $this->sitemapGenerator->addNode($e->getNode(), $e->getNodeRow());
    }
    
}