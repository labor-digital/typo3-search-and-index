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
 * Last modified: 2019.09.02 at 17:43
 */

namespace LaborDigital\T3SAI\Configuration;


use LaborDigital\Typo3BetterApi\Container\TypoContainerInterface;
use LaborDigital\Typo3BetterApi\ExtConfig\ExtConfigContext;

class SearchConfigurator
{
    
    /**
     * @var \LaborDigital\T3SAI\Configuration\SearchConfig
     */
    protected $config;
    
    /**
     * @var \LaborDigital\Typo3BetterApi\Container\TypoContainerInterface
     */
    protected $container;
    
    /**
     * @var \LaborDigital\Typo3BetterApi\ExtConfig\ExtConfigContext
     */
    protected $context;
    
    /**
     * SearchConfigurator constructor.
     *
     * @param   \LaborDigital\T3SAI\Configuration\SearchConfig                 $config
     * @param   \LaborDigital\Typo3BetterApi\ExtConfig\ExtConfigContext        $context
     * @param   \LaborDigital\Typo3BetterApi\Container\TypoContainerInterface  $container
     */
    public function __construct(SearchConfig $config, ExtConfigContext $context, TypoContainerInterface $container)
    {
        $this->config    = $config;
        $this->container = $container;
        $this->context   = $context;
    }
    
    /**
     * Returns the instance of a search domain (called "default", by default. Who knew!?).
     * Multiple search domains make sense if you handle multiple domains within the same TYPO3 instance.
     *
     * @param   string  $domainKey
     *
     * @return \LaborDigital\T3SAI\Configuration\SearchDomainConfig
     */
    public function getDomain(string $domainKey = 'default'): SearchDomainConfig
    {
        if (isset($this->config->domains[$domainKey])) {
            return $this->config->domains[$domainKey];
        }
        $i = $this->container->get(SearchDomainConfig::class, ['args' => [$this->context]]);
        
        return $this->config->domains[$domainKey] = $i;
    }
    
    /**
     * Removes a specific domain from the list of registered configurations
     *
     * @param   string  $domainKey
     */
    public function removeDomain(string $domainKey): void
    {
        unset($this->config->domains[$domainKey]);
    }
    
    /**
     * Returns true if a domain with the given key was registered, false if not
     *
     * @param   string  $domainKey
     *
     * @return bool
     */
    public function hasDomain(string $domainKey): bool
    {
        return isset($this->config->domains[$domainKey]);
    }
}
