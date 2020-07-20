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
 * Last modified: 2019.09.02 at 19:53
 */

namespace LaborDigital\T3SAI\Configuration;


use Closure;
use LaborDigital\T3SAI\Exception\SearchDomainNotFoundException;
use LaborDigital\T3SAI\Exception\SearchNotConfiguredException;
use TYPO3\CMS\Core\SingletonInterface;

class SearchConfigRepository implements SingletonInterface
{
    /**
     * Holds the config provider reference that is used to retrieve
     * the configuration object from the ext config option
     *
     * @var \Closure
     */
    protected $configProvider;
    
    /**
     * Holds the search config after it was requested from the config option
     *
     * @var \LaborDigital\T3SAI\Configuration\SearchConfig|null
     */
    protected $config;
    
    /**
     * Returns true if a domain with the given key is configured, false if not
     *
     * @param   string  $domainKey
     *
     * @return bool
     */
    public function hasDomainConfig(string $domainKey = 'default'): bool
    {
        return isset($this->getConfig()->domains[$domainKey]);
    }
    
    /**
     * Returns the domain configuration for the given domain key.
     *
     * @param   string  $domainKey
     *
     * @return \LaborDigital\T3SAI\Configuration\SearchDomainConfig
     * @throws \LaborDigital\T3SAI\Exception\SearchDomainNotFoundException
     */
    public function getDomainConfig(string $domainKey = 'default'): SearchDomainConfig
    {
        if (! $this->hasDomainConfig($domainKey)) {
            throw new SearchDomainNotFoundException('The domain with the given key: $domainKey was not registered!');
        }
        
        return $this->getConfig()->domains[$domainKey];
    }
    
    /**
     * Returns the list of all registered domain objects
     *
     * @return \LaborDigital\T3SAI\Configuration\SearchDomainConfig[]
     */
    public function getDomainConfigurations(): array
    {
        return $this->getConfig()->domains;
    }
    
    /**
     * Returns the search config object
     *
     * @return \LaborDigital\T3SAI\Configuration\SearchConfig
     * @throws \LaborDigital\T3SAI\Exception\SearchNotConfiguredException
     */
    protected function getConfig(): SearchConfig
    {
        if (empty($this->config)) {
            if (! is_callable($this->configProvider)) {
                throw new SearchNotConfiguredException('It seems like you did not configure the search and index extension using the ext config option!');
            }
            $this->config = call_user_func($this->configProvider);
        }
        
        return $this->config;
    }
    
    /**
     * Internal helper that sets the config provider reference that is used to retrieve
     * the configuration object from the ext config option
     *
     * @param   \Closure  $provider
     *
     * @deprecated Will be removed in v10
     */
    public function __setConfigProvider(Closure $provider): void
    {
        $this->configProvider = $provider;
    }
}
