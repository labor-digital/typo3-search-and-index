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
 * Last modified: 2022.04.12 at 11:02
 */

declare(strict_types=1);


namespace LaborDigital\T3sai\Core\Indexer\Node\Converter;


use LaborDigital\T3sai\Core\Soundex\SoundexGeneratorInterface;
use LaborDigital\T3sai\Core\StopWords\StopWordListInterface;

class WordExtractor
{
    /**
     * Receives the output of {@link \LaborDigital\T3sai\Core\Indexer\Node\Converter\TextConverter::convertText()}
     * and extracts a prioritized
     *
     * @param   \LaborDigital\T3sai\Core\StopWords\StopWordListInterface    $stopWordList
     * @param   \LaborDigital\T3sai\Core\Soundex\SoundexGeneratorInterface  $soundexGenerator
     * @param   array                                                       $textList
     *
     * @return array
     */
    public function extractWords(
        StopWordListInterface $stopWordList,
        SoundexGeneratorInterface $soundexGenerator,
        array $textList
    ): array
    {
        $list = [];
        
        foreach ($textList as $listType => $rowList) {
            $isKeyword = $listType === 'keywords';
            
            foreach ($rowList as $row) {
                [$text, $priority] = $row;
                
                foreach ($this->convertTextToWordList($text) as $word => $occurrences) {
                    if (! isset($list[$word])) {
                        $list[$word] = [
                            'occurrences' => $occurrences,
                            'priority' => 0,
                            'occurrencesInTexts' => 0,
                            'isKeyword' => false,
                            'isStopWord' => false,
                            'soundex' => $soundexGenerator->generate($word),
                        ];
                    }
                    
                    if ($stopWordList->isStopWord($word)) {
                        $list[$word]['isStopWord'] = true;
                        continue;
                    }
                    
                    $list[$word]['occurrences'] += $occurrences;
                    $list[$word]['priority'] += $priority;
                    $list[$word]['occurrencesInTexts']++;
                    
                    if ($isKeyword) {
                        $list[$word]['isKeyword'] = true;
                    }
                }
            }
        }
        
        return $list;
    }
    
    /**
     * Receives a text that should be converted into a list of words (all lower case)
     * and the number of their occurrences in the given text
     *
     * @param   string  $text
     *
     * @return array
     */
    protected function convertTextToWordList(string $text): array
    {
        $wordList = [];
        $text = preg_replace('~[^\\p{L}0-9]~iu', ' ', $text);
        foreach (explode(' ', $text) as $word) {
            $word = trim($word);
            
            if (empty($word) || is_numeric($word)) {
                continue;
            }
            
            $word = strtolower($word);
            $wordList[$word] = isset($wordList[$word]) ? $wordList[$word] + 1 : 1;
        }
        
        return $wordList;
    }
}