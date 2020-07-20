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
 * Last modified: 2019.03.24 at 11:50
 */

namespace LaborDigital\T3SAI\StopWords;


use TYPO3\CMS\Core\SingletonInterface;

abstract class AbstractStopWordChecker implements StopWordCheckerInterface, SingletonInterface
{
    /**
     * Should be provided by every check instance
     */
    protected const STOP_WORDS = [];
    
    /**
     * Internal cache to avoid multiple calculations
     *
     * @var array
     */
    protected $knownStopWords = [];
    
    /**
     * @inheritDoc
     */
    public function isStopWord(string $word): bool
    {
        if (isset($this->knownStopWords[$word])) {
            return $this->knownStopWords[$word];
        }
        if (in_array($word, static::STOP_WORDS, true)) {
            return $this->knownStopWords[$word] = true;
        }
        foreach (static::STOP_WORDS as $sw) {
            if (stripos($word, $sw) !== 0) {
                continue;
            }
            if (abs(strlen($sw) - strlen($word)) < 3) {
                return $this->knownStopWords[$word] = true;
            }
        }
        
        return $this->knownStopWords[$word] = false;
    }
}
