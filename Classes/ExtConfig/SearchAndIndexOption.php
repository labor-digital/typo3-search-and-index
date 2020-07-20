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
 * Last modified: 2019.05.23 at 13:06
 */

namespace LaborDigital\T3SAI\ExtConfig;


use LaborDigital\T3SAI\Configuration\SearchConfigGenerator;
use LaborDigital\T3SAI\Configuration\SearchConfigRepository;
use LaborDigital\Typo3BetterApi\ExtConfig\Option\AbstractExtConfigOption;

/**
 * Class SearchAndIndexOption
 *
 * Can be used to configure the search extension
 *
 * @package LaborDigital\T3SAI\ExtConfig
 */
class SearchAndIndexOption extends AbstractExtConfigOption
{
    
    /**
     * SearchAndIndexOption constructor.
     *
     * @param   \LaborDigital\T3SAI\Configuration\SearchConfigRepository  $configRepository
     */
    public function __construct(SearchConfigRepository $configRepository)
    {
        $configRepository->__setConfigProvider(function () {
            return $this->getCachedStackValueOrRun('config', SearchConfigGenerator::class);
        });
    }
    
    /**
     * Adds a new search configuration object to the list of available configurations
     *
     * @param   string  $configClass  The name of the configuration class that should be registered.
     *                                The class should implement the SearchConfigInterface
     *
     * @return \LaborDigital\T3SAI\ExtConfig\SearchAndIndexOption
     * @see \LaborDigital\T3SAI\Configuration\SearchConfigInterface
     */
    public function registerSearchConfig(string $configClass): SearchAndIndexOption
    {
        return $this->addRegistrationToCachedStack('config', 'config', $configClass);
    }
    
    /**
     * Removes a previously registered configuration class from the list of available configurations
     *
     * @param   string  $configClass  The name of the configuration class that should be removed as configuration
     *
     * @return \LaborDigital\T3SAI\ExtConfig\SearchAndIndexOption
     * @see \LaborDigital\T3SAI\Configuration\SearchConfigInterface
     */
    public function removeSearchConfig(string $configClass): SearchAndIndexOption
    {
        return $this->removeFromCachedStack('config', 'config', $configClass);
    }
}
