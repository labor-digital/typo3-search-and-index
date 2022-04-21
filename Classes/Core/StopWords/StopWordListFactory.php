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
 * Last modified: 2022.04.12 at 14:53
 */

declare(strict_types=1);


namespace LaborDigital\T3sai\Core\StopWords;


use LaborDigital\T3ba\Core\Di\ContainerAwareTrait;
use LaborDigital\T3sai\Core\StopWords\Implementation\EnglishStopWords;
use LaborDigital\T3sai\Core\StopWords\Implementation\GermanStopWords;
use LaborDigital\T3sai\Core\StopWords\Implementation\NullStopWords;
use TYPO3\CMS\Core\SingletonInterface;

class StopWordListFactory implements SingletonInterface
{
    use ContainerAwareTrait;
    
    protected const STATIC_LISTS
        = [
            'de' => GermanStopWords::class,
            'en' => EnglishStopWords::class,
        ];
    
    /**
     * Creates a new stop word list instance for the provided domain configuration and language
     *
     * @param   array   $domainConfig
     * @param   string  $languageCode
     *
     * @return \LaborDigital\T3sai\StopWords\StopWordListInterface
     */
    public function makeStopWordList(array $domainConfig, string $languageCode): StopWordListInterface
    {
        return $this->makeInstance(
            $this->resolveListClass($domainConfig, $languageCode)
        );
    }
    
    /**
     * Tries to resolve the correct class name for the currently required stop word list
     *
     * @param   array   $domainConfig
     * @param   string  $languageCode
     *
     * @return string
     */
    protected function resolveListClass(array $domainConfig, string $languageCode): string
    {
        return $domainConfig['stopWords'][$languageCode]
               ?? $domainConfig['stopWords']['default']
                  ?? static::STATIC_LISTS[$languageCode]
                     ?? NullStopWords::class;
    }
    
}