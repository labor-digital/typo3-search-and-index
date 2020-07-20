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
 * Last modified: 2020.07.16 at 18:48
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
 * Last modified: 2019.03.23 at 16:24
 */

namespace LaborDigital\T3SAI\Indexer;

use DateTime;
use LaborDigital\T3SAI\Exception\Indexer\IndexNodeException;
use LaborDigital\T3SAI\StopWords\StopWordCheckerInterface;

class ContentParser
{
    
    /**
     * Receives a list of inputs in which every child's key "0" represents the text content
     * and key "1" the priority for the given content. This method will then remove all irrelevant chars
     * from the string and convert it into a word list, containing occurrences and priorities for each word.
     *
     * @param   iterable  $input
     *
     * @return array
     */
    public function parseInputList(iterable $input): array
    {
        $cText     = [];
        $cWordList = [];
        foreach ($input as $item) {
            // Unify the text and skip if the item is empty
            $text = $this->unifyText($item[0]);
            if ($text === '') {
                continue;
            }
            $cText[] = $text;
            
            // Create a prioritized word list out of the readable text
            foreach ($this->convertTextToWordList($text) as $word => $count) {
                // Create a new word entry
                if (! isset($cWordList[$word])) {
                    $cWordList[$word] = [
                        'count'    => 0,
                        'priority' => [],
                    ];
                }
                
                // Update the merged values
                $cWordList[$word]['count']      += $count;
                $cWordList[$word]['priority'][] = $item[1];
            }
        }
        
        // Calculate word priorities
        foreach ($cWordList as $k => $word) {
            $cWordList[$k]['priority'] = array_sum($word['priority']) / count($word['priority']);
        }
        
        // Combine the readable text and return the result
        return [
            'text'  => implode(' ', $cText),
            'words' => $cWordList,
        ];
    }
    
    /**
     * Should receive multiple results of parseInputList() and combines them into a single, unified word list
     * that have their priorities and word counts combined into a list of all words in the source node.
     *
     * It will also apply priority penalties to stop words using the given stopWordChecker
     *
     * @param   StopWordCheckerInterface  $checker   The stop word checker to calculate penalties
     * @param   array                     $keywords  The list of keywords to prioritize higher than normal words
     * @param   mixed                     ...$lists
     *
     * @return array
     */
    public function processWordLists(StopWordCheckerInterface $checker, array $keywords, ...$lists): array
    {
        // Merge the given word lists
        $wordList = [];
        array_unshift($lists, $keywords);
        foreach ($lists as $list) {
            foreach ($list as $word => $data) {
                $word = (string)$word;
                // Create a new word entry
                if (! isset($wordList[$word])) {
                    $wordList[$word] = [
                        'count'    => 0,
                        'priority' => 0,
                    ];
                }
                
                // Update the merged values
                $wordList[$word]['count']    += $data['count'];
                $wordList[$word]['priority'] += ($data['priority'] * $data['count']);
            }
        }
        
        // Finalize priorities
        $maxPriority = 0;
        foreach ($wordList as $word => $data) {
            $word     = (string)$word;
            $priority = max($data['priority'], 10);
            // Add additional priority based on the word length (+5% points per character)
            $priority += ($priority * 0.05 * strlen($word));
            // Add additional priority based on the occurrences of the word (+10% per occurrence)
            $priority += ($priority * 0.1 * $data['count']);
            // Apply priority penalty to stop words (0 if stop word)
            if ($checker->isStopWord($word)) {
                $priority = 0;
            }
            // Detect max priority
            if ($priority > $maxPriority) {
                $maxPriority = $priority;
            }
            $wordList[$word] = $priority;
        }
        
        // Unify the priorities to a range from 0 - 100
        foreach ($wordList as $word => $priority) {
            $wordList[$word] = $maxPriority === 0 ? 0 : $priority / $maxPriority * 100;
        }
        
        // Buff all keywords by their priority
        if (! empty($keywords['words'])) {
            foreach ($keywords['words'] as $word => $wordMeta) {
                if (! isset($wordList[$word])) {
                    continue;
                }
                $wordList[$word] += $wordMeta['priority'];
            }
        }
        
        // Done
        return $wordList;
    }
    
    /**
     * Returns the stringified, absolute version of the url to a node, or dies throwing an exception
     *
     * @param   \LaborDigital\T3SAI\Indexer\IndexNode       $node
     * @param   \LaborDigital\T3SAI\Indexer\IndexerContext  $context
     *
     * @return string
     * @throws \LaborDigital\T3SAI\Exception\Indexer\IndexNodeException
     */
    public function stringifyNodeLink(IndexNode $node, IndexerContext $context): string
    {
        if (! empty($node->getHardLink())) {
            return $node->getHardLink();
        }
        
        if ($node->getLink() === null) {
            throw IndexNodeException::makeInstance($node,
                'Error while creating node: "' . $node->getTitle() . '" there was no link defined!');
        }
        
        return $node->getLink()->withLanguage($context->getLanguage())->build();
    }
    
    /**
     * Generates the absolute url to a node's image file or returns an empty string if there was none
     *
     * @param   \LaborDigital\T3SAI\Indexer\IndexNode       $node
     * @param   \LaborDigital\T3SAI\Indexer\IndexerContext  $context
     *
     * @return string
     */
    public function stringifyNodeImage(IndexNode $node, IndexerContext $context): string
    {
        // Iterable image collection -> Only select the first image
        $image = $node->getImage();
        if (is_iterable($image)) {
            $realImage = null;
            
            /** @noinspection LoopWhichDoesNotLoopInspection */
            foreach ($image as $i) {
                $realImage = $i;
                break;
            }
            $image = $realImage;
        }
        
        // No image
        if (empty($image)) {
            return '';
        }
        
        // Url as image
        if (is_string($node->getImage()) && filter_var($node->getImage(), FILTER_VALIDATE_URL)) {
            return $node->getImage();
        }
        
        // Try a fall file
        return $context->getLinkService()->getFileLink($image);
    }
    
    /**
     * Calculates the default priority of a node based on the content and the timestamp
     *
     * @param   \LaborDigital\T3SAI\Indexer\IndexNode  $node   The node to calculate the priority for
     * @param   array                                  $words  The result of $this->processWordLists() to use for priority calculation
     *
     * @return float
     */
    public function calculateNodePriority(IndexNode $node, array $words): float
    {
        $nodePriority = $node->getPriority();
        
        // Modify priority based on content
        $wordPriority = array_sum($words) / max(1, count($words));
        $wordPriority = (min($wordPriority, $nodePriority) / max($nodePriority, $wordPriority, 1)) * 100;
        // Only apply guard rails if they are required
        $tmp          = $nodePriority + (min(100, $wordPriority) / 3);
        $nodePriority = $nodePriority > 100 ? $tmp : min(100, $tmp);
        
        // Modify content based on timestamp
        $timestamp    = $node->getTimestamp();
        $dateDiff     = $timestamp->diff(new DateTime('now'));
        $nodePriority -= min(40, abs($nodePriority) * $dateDiff->days / 7 / 2 / 50);
        $nodePriority = max(-20, $nodePriority);
        
        return (float)$nodePriority;
    }
    
    /**
     * Receives a text that should be converted into a list of words (all lower case) and the number of their occurrences in the given text
     *
     * @param   string  $text
     *
     * @return array
     */
    protected function convertTextToWordList(string $text): array
    {
        $wordList = [];
        $text     = preg_replace('~[^\\p{L}0-9]~iu', ' ', $text);
        foreach (explode(' ', $text) as $word) {
            $word = (string)$word;
            $word = trim($word);
            if (empty($word)) {
                continue;
            }
            $word            = strtolower($word);
            $wordList[$word] = isset($wordList[$word]) ? $wordList[$word] + 1 : 1;
        }
        
        return $wordList;
    }
    
    /**
     * Is used to remove all irrelevant contents from a given text / list of text / object
     * and return a clean text content without html tags and a like
     *
     * @param   string|null|iterable|object  $item
     *
     * @return string
     */
    protected function unifyText($item): string
    {
        if (is_numeric($item)) {
            $item = (string)$item;
        }
        if (! is_string($item)) {
            if ($item === null) {
                $item = '';
            } elseif (is_iterable($item)) {
                $textString = [];
                foreach ($item as $text) {
                    $textString[] = $this->unifyText($text);
                }
                $item = implode(' ', $textString);
            } else {
                $item = preg_replace('~{[\"\\\\]}:~', ' ', json_encode($item, JSON_THROW_ON_ERROR));
            }
        }
        $item = trim($this->repairBrokenChars($item));
        if ($item === '') {
            return '';
        }
        
        // Remove html tags
        $item = str_replace(['&shy;', '&nbsp;'], ['', ' '], $item);
        $item = html_entity_decode($item);
        $item = preg_replace('~<\s*br\s*/?\s*>~i', ' ', $item);
        $item = strip_tags($item);
        
        // Remove line breaks, word breaks "-\s\r?\n"
        $item = preg_replace('~-(?:\\s|\\s\\r?\\n|\\r?\\n)~i', ' ', $item);
        $item = preg_replace('~\\r?\\n~i', ' ', $item);
        
        // Remove multiple spaces
        $item = preg_replace('~\\s+~', ' ', $item);
        
        return (string)$item;
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
        $expectedLength   = 0;
        $expectedIndex    = 0;
        $i                = 0;
        $out              = '';
        while ($i < strlen($contents)) {
            $byte = ord($contents[$i]);
            // Start of a new character?
            if ($expectingNewChar) {
                // 1-byte ASCII chars, append to output
                if ($byte < 0x80) {
                    $out .= $contents[$i];
                } // First byte >= 0xF0 means a 4-byte char
                elseif ($byte >= 0xF0) {
                    $expectingNewChar = false;
                    $expectedLength   = 4;
                    $expectedIndex    = 2;
                } // First byte >= 0xE0 means a 3-byte char
                elseif ($byte >= 0xE0) {
                    $expectingNewChar = false;
                    $expectedLength   = 3;
                    $expectedIndex    = 2;
                } // First byte >= 0xC0 means a 2-byte char
                elseif ($byte >= 0xC0) {
                    $expectingNewChar = false;
                    $expectedLength   = 2;
                    $expectedIndex    = 2;
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
                    $c                = substr($contents, $i - $expectedLength + 1,
                        $expectedLength);
                    $out              .= iconv('UTF-8', 'UTF-8//IGNORE', $c);
                    $expectingNewChar = true;
                }
            }
            // Advance to the next byte
            $i++;
        }
        
        return $out;
    }
}
