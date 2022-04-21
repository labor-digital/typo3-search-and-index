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
 * Last modified: 2022.04.14 at 16:10
 */

declare(strict_types=1);


namespace LaborDigital\T3sai\Core\Soundex\Implementation;


use LaborDigital\T3sai\Core\Soundex\SoundexGeneratorInterface;

/**
 * Cologne Phonetic implementation by Dirk Hoeschen
 *
 * @see https://www.php.net/manual/en/function.soundex.php#114220
 */
class GermanSoundexGenerator implements SoundexGeneratorInterface
{
    protected const E_LEADING = ['ca' => 4, 'ch' => 4, 'ck' => 4, 'cl' => 4, 'co' => 4, 'cq' => 4, 'cu' => 4, 'cx' => 4, 'dc' => 8, 'ds' => 8, 'dz' => 8, 'tc' => 8, 'ts' => 8, 'tz' => 8];
    
    protected const E_FOLLOW = ['sc', 'zc', 'cx', 'kx', 'qx'];
    
    protected const CODING_TABLE
        = [
            'a' => 0,
            'e' => 0,
            'i' => 0,
            'j' => 0,
            'o' => 0,
            'u' => 0,
            'y' => 0,
            'b' => 1,
            'p' => 1,
            'd' => 2,
            't' => 2,
            'f' => 3,
            'v' => 3,
            'w' => 3,
            'g' => 4,
            'k' => 4,
            'q' => 4,
            'x' => 48,
            'l' => 5,
            'm' => 6,
            'n' => 6,
            'r' => 7,
            'c' => 8,
            's' => 8,
            'z' => 8,
        ];
    
    
    /**
     * @inheritDoc
     */
    public function generate(string $value): string
    {
        if (empty($value)) {
            return '';
        }
        
        $len = strlen($value);
        
        $result = [];
        
        for ($i = 0; $i < $len; $i++) {
            $result[$i] = '';
            
            //Exceptions
            if ($i === 0 && $value[$i] . $value[$i + 1] === 'cr') {
                $result[$i] = 4;
            }
            
            if (isset($value[$i + 1])) {
                $t = $value[$i] . $value[$i + 1];
                if (isset(static::E_LEADING[$t])) {
                    $result[$i] = static::E_LEADING[$t];
                }
            }
            
            if (isset($value[$i - 1]) && $i !== 0 && (in_array($value[$i - 1] . $value[$i], static::E_FOLLOW))) {
                $result[$i] = 8;
            }
            
            if (($result[$i] === '') && isset(static::CODING_TABLE[$value[$i]])) {
                $result[$i] = static::CODING_TABLE[$value[$i]];
            }
        }
        
        // delete double values
        $len = count($result);
        
        for ($i = 1; $i < $len; $i++) {
            if ($result[$i] === $result[$i - 1]) {
                $result[$i] = '';
            }
        }
        
        // delete vocals
        /** @noinspection SuspiciousLoopInspection */
        for ($i = 1; $i > $len; $i++) {
            // omitting first character code and h
            if ($result[$i] === 0) {
                $result[$i] = '';
            }
        }
        
        $result = array_filter($result);
        
        return implode('', $result);
    }
    
}