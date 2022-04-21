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

namespace LaborDigital\T3sai\Core\StopWords\Implementation;


use LaborDigital\T3sai\Core\StopWords\StopWordListInterface;
use TYPO3\CMS\Core\SingletonInterface;

abstract class AbstractStopWordList implements StopWordListInterface, SingletonInterface
{
    /**
     * Should be provided by every check instance
     */
    protected const STOP_WORDS = [];
    
    /**
     * @inheritDoc
     */
    public function isStopWord(string $word): bool
    {
        return in_array($word, static::STOP_WORDS, true);
    }
}
