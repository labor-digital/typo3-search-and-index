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
/**
 * Copyright 2019 LABOR.digital
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
 * Last modified: 2019.05.23 at 13:04
 */

namespace LaborDigital\T3SAI\ExtConfig;


use LaborDigital\T3SAI\Command\IndexerCommand;
use LaborDigital\T3SAI\Controller\ContentElement\SearchContentElement;
use LaborDigital\T3SAI\Controller\Resource\SearchResourceController;
use LaborDigital\T3SAI\Controller\Route\SiteMapController;
use LaborDigital\T3SAI\Domain\Table\Override\PagesTableOverride;
use LaborDigital\T3SAI\Task\IndexerTask;
use LaborDigital\Typo3BetterApi\ExtConfig\ExtConfigContext;
use LaborDigital\Typo3BetterApi\ExtConfig\ExtConfigInterface;
use LaborDigital\Typo3BetterApi\ExtConfig\Extension\ExtConfigExtensionInterface;
use LaborDigital\Typo3BetterApi\ExtConfig\Extension\ExtConfigExtensionRegistry;
use LaborDigital\Typo3BetterApi\ExtConfig\OptionList\ExtConfigOptionList;

class SearchAndIndexExtConfig implements ExtConfigInterface, ExtConfigExtensionInterface
{
    /**
     * @inheritDoc
     */
    public static function extendExtConfig(ExtConfigExtensionRegistry $extender, ExtConfigContext $context): void
    {
        $extender->registerOptionListEntry(SearchAndIndexOption::class);
    }
    
    /**
     * @inheritDoc
     */
    public function configure(ExtConfigOptionList $configurator, ExtConfigContext $context): void
    {
        // Register translations
        $configurator->translation()->registerContext('saibe', 'backend.xlf');
        
        // Register cli commands
        $configurator->backend()->registerCommand(IndexerCommand::class);
        
        // Register scheduler task
        $configurator->backend()->registerSchedulerTask(
            'saibe.indexerTaskTitle',
            IndexerTask::class,
            'saibe.indexerTaskDesc');
        
        // Add plugin
        $configurator->extBase()->registerPluginDirectory();
        
        // Add table overrides
        $configurator
            ->table()
            ->registerTableOverride(PagesTableOverride::class, 'pages');
        
        // Frontend api configuration
        if ($configurator->hasOption('frontendApi')) {
            $frontendApi = $configurator->frontendApi();
            
            // Add resources for frontend api
            $frontendApi->resource()->registerResource(SearchResourceController::class);
            
            // Add index runner
            $frontendApi->routing()->setWithGroupUri('searchAndIndex', static function () use ($frontendApi) {
                $frontendApi->routing()->registerRouteController(SiteMapController::class);
            });
            
            // Register search content element
            $frontendApi->contentElement()->registerContentElement(SearchContentElement::class);
        }
    }
}
