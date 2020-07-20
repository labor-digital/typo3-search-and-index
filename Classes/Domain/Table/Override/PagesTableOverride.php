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
 * Last modified: 2020.01.31 at 20:07
 */

namespace LaborDigital\T3SAI\Domain\Table\Override;

use LaborDigital\Typo3BetterApi\BackendForms\TcaForms\TcaTable;
use LaborDigital\Typo3BetterApi\ExtConfig\ExtConfigContext;
use LaborDigital\Typo3BetterApi\ExtConfig\Option\Table\TableConfigurationInterface;

class PagesTableOverride implements TableConfigurationInterface
{
    
    /**
     * @inheritDoc
     */
    public static function configureTable(TcaTable $table, ExtConfigContext $context, bool $isOverride): void
    {
        $type = $table->getType(1);
        $type->getTab('953')
             ->setLabel('saibe.t.pages.tab.sai')
             ->addMultiple(static function () use ($type) {
                 $type->getField('sai_index')
                      ->setLabel('saibe.t.pages.index')
                      ->applyPreset()->checkbox(['toggle', 'inverted']);
            
                 $type->getField('sai_sitemap')
                      ->setLabel('saibe.t.pages.sitemap')
                      ->applyPreset()->checkbox(['toggle', 'inverted']);
            
                 $type->getField('sai_keywords')
                      ->setLabel('saibe.t.pages.keywords')
                      ->applyPreset()->textArea();
            
                 $type->getField('sai_priority')
                      ->setLabel('saibe.t.pages.priority')
                      ->applyPreset()->input(['default' => '0'])
                      ->addConfig('min', -100)
                      ->addConfig('max', 100);
             });
    }
    
}
