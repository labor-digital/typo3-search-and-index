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
 * Last modified: 2022.04.12 at 10:36
 */

declare(strict_types=1);


namespace LaborDigital\T3sai\Core\Indexer\Node\Converter;


class TextConverter
{
    /**
     * Expects an iterable list of arrays in which the first child is the text content
     * and the second child is the numeric priority in a range of 0 - 100
     *
     * @param   iterable  $contentList
     *
     * @return array
     */
    public function convertText(iterable $contentList): array
    {
        $result = [];
        
        foreach ($contentList as $item) {
            $text = $this->normalizeText($item[0] ?? '');
            
            if ($text === '') {
                continue;
            }
            
            $result[] = [
                $text,
                $item[1] ?? 0,
            ];
        }
        
        return $result;
    }
    
    /**
     * Merges multiple outputs of convertText into one, combined string
     *
     * @param   array  ...$texts
     *
     * @return string
     */
    public function mergeTexts(array ...$texts): string
    {
        $output = [];
        
        foreach ($texts as $rows) {
            foreach ($rows as $row) {
                $output[] = $row[0];
            }
        }
        
        return implode(' [...] ', $output);
    }
    
    /**
     * Is used to remove all irrelevant contents (HTML, linebreaks and alike) from a given text
     *
     * @param   string  $text
     *
     * @return string
     */
    protected function normalizeText(string $text): string
    {
        $text = trim($this->repairBrokenChars($text));
        
        if ($text === '') {
            return '';
        }
        
        // Remove html tags
        $text = strip_tags(
            preg_replace(
                '~<\s*br\s*/?\s*>~i', ' ',
                html_entity_decode(
                    str_replace(
                        ['&shy;', '&nbsp;'], ['', ' '],
                        $text)
                )
            )
        );
        
        // Remove line breaks, word breaks "-\s\r?\n" and multiple spaces
        return preg_replace(['~-(?:\\s|\\s\\r?\\n|\\r?\\n)~i', '~\\r?\\n~i', '~\\s+~'], ' ', $text);
    }
    
    /**
     * Receives a string input and tires to fix broken utf8 chars
     *
     * @param   string  $contents
     *
     * @return string
     * @see https://gist.github.com/davidsklar/69300b66f6c3c3ab3d6a
     */
    protected function repairBrokenChars(string $contents): string
    {
        $expectingNewChar = true;
        $expectedLength = 0;
        $expectedIndex = 0;
        $i = 0;
        $out = '';
        $contentLength = strlen($contents);
        while ($i < $contentLength) {
            $byte = ord($contents[$i]);
            // Start of a new character?
            if ($expectingNewChar) {
                // 1-byte ASCII chars, append to output
                if ($byte < 0x80) {
                    $out .= $contents[$i];
                } // First byte >= 0xF0 means a 4-byte char
                elseif ($byte >= 0xF0) {
                    $expectingNewChar = false;
                    $expectedLength = 4;
                    $expectedIndex = 2;
                } // First byte >= 0xE0 means a 3-byte char
                elseif ($byte >= 0xE0) {
                    $expectingNewChar = false;
                    $expectedLength = 3;
                    $expectedIndex = 2;
                } // First byte >= 0xC0 means a 2-byte char
                elseif ($byte >= 0xC0) {
                    $expectingNewChar = false;
                    $expectedLength = 2;
                    $expectedIndex = 2;
                }
            }
            // Not the start of a new char, but expecting
            // one of the bytes in a multibyte char
            else {
                // Subsequent bytes in a multibyte char have
                // to be between 0x80 and 0xC0. If it's not
                // then something is corrupt, so add the replacement
                // char to the output and look for a new char
                if (! (($byte >= 0x80) && ($byte < 0xC0))) {
                    $expectingNewChar = true;
                    // This prevents $i++ at the end of the loop,
                    // since this "corrupt" byte could be the start
                    // of a new character
                    continue;
                }
                
                // A valid byte, but we haven't gotten as many
                // bytes for this char yet as we need
                if ($expectedIndex < $expectedLength) {
                    $expectedIndex++;
                }
                // Gotten all the bytes for the valid multibyte char,
                // put them in the output and expect a new character
                // next.
                else {
                    $c = substr($contents, $i - $expectedLength + 1,
                        $expectedLength);
                    $out .= iconv('UTF-8', 'UTF-8//IGNORE', $c);
                    $expectingNewChar = true;
                }
            }
            // Advance to the next byte
            $i++;
        }
        
        return $out;
    }
}