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
 * Last modified: 2022.04.14 at 16:06
 */

declare(strict_types=1);


namespace LaborDigital\T3sai\Core\Soundex;


use LaborDigital\T3ba\Core\Di\ContainerAwareTrait;
use LaborDigital\T3sai\Core\Soundex\Implementation\DefaultSoundexGenerator;
use LaborDigital\T3sai\Core\Soundex\Implementation\GermanSoundexGenerator;
use TYPO3\CMS\Core\SingletonInterface;

class SoundexGeneratorFactory implements SingletonInterface
{
    use ContainerAwareTrait;
    
    protected const STATIC_GENERATORS
        = [
            'de' => GermanSoundexGenerator::class,
        ];
    
    /**
     * Creates a new soundex generator instance for the provided domain configuration and language
     *
     * @param   array   $domainConfig
     * @param   string  $languageCode
     *
     * @return \LaborDigital\T3sai\Core\Soundex\SoundexGeneratorInterface
     */
    public function makeSoundexGenerator(array $domainConfig, string $languageCode): SoundexGeneratorInterface
    {
        return $this->makeInstance(
            $this->resolveGeneratorClass($domainConfig, $languageCode)
        );
    }
    
    /**
     * Tries to resolve the correct generator class for the given domain config and language code
     *
     * @param   array   $domainConfig
     * @param   string  $languageCode
     *
     * @return string
     */
    protected function resolveGeneratorClass(array $domainConfig, string $languageCode): string
    {
        return $domainConfig['soundex'][$languageCode]
               ?? $domainConfig['soundex']['default']
                  ?? static::STATIC_GENERATORS[$languageCode]
                     ?? DefaultSoundexGenerator::class;
    }
}