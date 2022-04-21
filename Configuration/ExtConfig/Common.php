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
use LaborDigital\T3ba\ExtConfigHandler\Fluid\ConfigureFluidInterface;
use LaborDigital\T3ba\ExtConfigHandler\Fluid\FluidConfigurator;
use LaborDigital\T3ba\ExtConfigHandler\Translation\ConfigureTranslationInterface;
use LaborDigital\T3ba\ExtConfigHandler\Translation\TranslationConfigurator;

class Common implements ConfigureTranslationInterface, ConfigureFluidInterface
{
    /**
     * @inheritDoc
     */
    public static function configureTranslation(TranslationConfigurator $configurator, ExtConfigContext $context): void
    {
        $configurator->registerNamespace('t3saibe', 'backend.xlf')
                     ->registerNamespace('t3sai');
    }
    
    /**
     * @inheritDoc
     */
    public static function configureFluid(FluidConfigurator $configurator, ExtConfigContext $context): void
    {
        $configurator->registerViewHelpers('t3sai');
    }
    
    
}