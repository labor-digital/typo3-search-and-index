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
 * Last modified: 2022.04.25 at 13:44
 */

declare(strict_types=1);


namespace LaborDigital\T3sai\Api\Bundle;


use LaborDigital\T3ba\ExtConfig\SiteBased\SiteConfigContext;
use LaborDigital\T3fa\ExtConfigHandler\Api\ApiBundleInterface;
use LaborDigital\T3fa\ExtConfigHandler\Api\ApiConfigurator;
use LaborDigital\T3fa\ExtConfigHandler\Api\Resource\ResourceCollector;
use LaborDigital\T3sai\Api\Route\SitemapController;

class SitemapBundle implements ApiBundleInterface
{
    /**
     * @inheritDoc
     */
    public static function registerResources(ResourceCollector $collector, SiteConfigContext $context, array $options): void
    {
    }
    
    /**
     * @inheritDoc
     */
    public static function configureSite(ApiConfigurator $configurator, SiteConfigContext $context, array $options): void
    {
        $configurator->routing()->routes('/')
                     ->get('/sitemap.xml', [SitemapController::class, 'sitemapAction']);
    }
    
}