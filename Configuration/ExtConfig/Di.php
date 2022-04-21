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
 * Last modified: 2022.04.11 at 12:48
 */

declare(strict_types=1);


namespace LaborDigital\T3sai\Configuration\ExtConfig;


use LaborDigital\T3ba\ExtConfig\ExtConfigContext;
use LaborDigital\T3ba\ExtConfigHandler\Di\DefaultDiConfig;
use LaborDigital\T3sai\Core\Indexer\Queue\Implementation\DomainHandler;
use LaborDigital\T3sai\Core\Indexer\Queue\Implementation\EnvironmentSimulationHandler;
use LaborDigital\T3sai\Core\Indexer\Queue\Implementation\LanguageHandler;
use LaborDigital\T3sai\Core\Indexer\Queue\Implementation\NodeHandler;
use LaborDigital\T3sai\Core\Indexer\Queue\Implementation\RecordIndexerHandler;
use LaborDigital\T3sai\Core\Indexer\Queue\Implementation\SiteHandler;
use LaborDigital\T3sai\Core\Indexer\Queue\Implementation\SitemapGenerationHandler;
use LaborDigital\T3sai\Core\Indexer\Queue\Runner;
use LaborDigital\T3sai\Core\Lookup\Backend\Processor\AutocompleteProcessor;
use LaborDigital\T3sai\Core\Lookup\Backend\Processor\SearchCountProcessor;
use LaborDigital\T3sai\Core\Lookup\Backend\Processor\SearchProcessor;
use LaborDigital\T3sai\Core\Lookup\Backend\Processor\TagsProcessor;
use LaborDigital\T3sai\Core\Lookup\Backend\SqlQuery\Mysql\MysqlQueryProvider;
use LaborDigital\T3sai\Domain\Repository\SearchRepository;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

class Di extends DefaultDiConfig
{
    /**
     * @inheritDoc
     */
    public static function configure(ContainerConfigurator $configurator, ContainerBuilder $containerBuilder, ExtConfigContext $context): void
    {
        static::autoWire([
            'Classes/Event',
            'Classes/Exception',
        ]);
        
        $services = $configurator->services();
        
        $services->get(Runner::class)
                 ->arg('$handlers', [
                     service(SitemapGenerationHandler::class),
                     service(SiteHandler::class),
                     service(DomainHandler::class),
                     service(LanguageHandler::class),
                     service(EnvironmentSimulationHandler::class),
                     service(RecordIndexerHandler::class),
                     service(NodeHandler::class),
                 ]);
        
        $services->get(SearchRepository::class)
                 ->call('addSqlQueryProvider', [service(MysqlQueryProvider::class)])
                 ->call('addResultProcessor', [service(AutocompleteProcessor::class)])
                 ->call('addResultProcessor', [service(SearchProcessor::class)])
                 ->call('addResultProcessor', [service(TagsProcessor::class)])
                 ->call('addResultProcessor', [service(SearchCountProcessor::class)]);
    }
    
    /**
     * @inheritDoc
     */
    public static function configureRuntime(Container $container, ExtConfigContext $context): void
    {
    }
}