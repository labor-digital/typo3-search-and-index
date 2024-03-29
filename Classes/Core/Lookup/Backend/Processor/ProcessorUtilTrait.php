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
 * Last modified: 2022.04.20 at 17:14
 */

declare(strict_types=1);


namespace LaborDigital\T3sai\Core\Lookup\Backend\Processor;


use Traversable;

trait ProcessorUtilTrait
{
    /**
     * Used to convert any kind of iterable into an array
     *
     * @param   iterable  $rows
     *
     * @return array
     */
    protected function iterableToArray(iterable $rows): array
    {
        return $rows instanceof Traversable ? iterator_to_array($rows, false) : (array)$rows;
    }
    
    /**
     * Internal helper to split up a given text into a list of comparable words
     *
     * @param   string  $text
     * @param   bool    $forHumans  If set to true punctuation and casing will be kept intact
     *
     * @return array
     */
    protected function splitTextIntoWords(string $text, bool $forHumans = false): array
    {
        if ($forHumans) {
            return array_values(
                array_filter(
                    explode(' ', $text)
                )
            );
        }
        
        return array_values(
            array_unique(
                array_filter(
                    explode(' ',
                        preg_replace('~[^\\w\\s]*~u', '', $text)
                    )
                )
            )
        );
    }
}