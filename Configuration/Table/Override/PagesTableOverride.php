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
 * Last modified: 2022.04.11 at 13:17
 */

declare(strict_types=1);


namespace LaborDigital\T3sai\Configuration\Table\Override;


use LaborDigital\T3ba\ExtConfig\ExtConfigContext;
use LaborDigital\T3ba\ExtConfigHandler\Table\ConfigureTcaTableInterface;
use LaborDigital\T3ba\ExtConfigHandler\Table\TcaTableNameProviderInterface;
use LaborDigital\T3ba\Tool\Tca\Builder\Type\Table\TcaTable;

class PagesTableOverride implements ConfigureTcaTableInterface, TcaTableNameProviderInterface
{
    /**
     * @inheritDoc
     */
    public static function getTableName(): string
    {
        return 'pages';
    }
    
    /**
     * @inheritDoc
     */
    public static function configureTable(TcaTable $table, ExtConfigContext $context): void
    {
        $type = $table->getType(1);
        $type->getNewTab()
             ->setLabel('t3saibe.t.pages.tab.sai')
             ->addMultiple(static function () use ($type) {
                 $type->getField('sai_sitemap')
                      ->setLabel('t3saibe.t.pages.sitemap')
                      ->applyPreset()->checkbox(['toggle', 'inverted'])->setDefault(1);
            
                 $type->getField('no_search')
                      ->setLabel('t3saibe.t.pages.index')
                      ->moveTo(['before', 'sai_sitemap']);
            
                 $type->getField('sai_keywords')
                      ->setLabel('t3saibe.t.pages.keywords')
                      ->applyPreset()->textArea();
            
                 $type->getField('sai_priority')
                      ->setLabel('t3saibe.t.pages.priority')
                      ->applyPreset()->input(['default' => '0'])
                      ->addConfig('min', -100)
                      ->addConfig('max', 100);
             });
    }
}
