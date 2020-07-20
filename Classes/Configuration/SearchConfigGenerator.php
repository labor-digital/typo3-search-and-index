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
 * Last modified: 2019.09.02 at 18:19
 */

namespace LaborDigital\T3SAI\Configuration;


use LaborDigital\T3SAI\Exception\SearchAndIndexException;
use LaborDigital\Typo3BetterApi\ExtConfig\ExtConfigContext;
use LaborDigital\Typo3BetterApi\ExtConfig\Option\CachedStackGeneratorInterface;

class SearchConfigGenerator implements CachedStackGeneratorInterface
{
    
    /**
     * @inheritDoc
     */
    public function generate(array $stack, ExtConfigContext $context, array $additionalArguments, $option)
    {
        $data         = reset($stack);
        $config       = $context->getInstanceOf(SearchConfig::class);
        $configurator = $context->getInstanceOf(SearchConfigurator::class, [$config, $context]);
        $context->runWithCachedValueDataScope($data,
            static function (string $configClass) use ($context, $configurator) {
                if (! class_exists($configClass) ||
                    ! in_array(SearchConfigInterface::class, class_implements($configClass), true)) {
                    throw new SearchAndIndexException(
                        'The given configuration class: $configClass does not implement the required interface: ' .
                        SearchConfigInterface::class);
                }
                call_user_func([$configClass, 'configure'], $configurator, $context);
            });
        
        return $config;
    }
    
}
