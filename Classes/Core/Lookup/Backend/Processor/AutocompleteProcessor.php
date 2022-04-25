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
 * Last modified: 2022.04.19 at 18:31
 */

declare(strict_types=1);


namespace LaborDigital\T3sai\Core\Lookup\Backend\Processor;


use LaborDigital\T3sai\Core\Lookup\Lexer\ParsedInput;
use LaborDigital\T3sai\Core\Lookup\Request\LookupRequest;
use LaborDigital\T3sai\Core\Soundex\SoundexGeneratorInterface;
use LaborDigital\T3sai\Core\StopWords\StopWordListInterface;
use Neunerlei\Arrays\Arrays;

class AutocompleteProcessor implements LookupResultProcessorInterface
{
    /**
     * @var ParsedInput
     */
    protected $input;
    
    /**
     * @var StopWordListInterface
     */
    protected $stopWordList;
    
    /**
     * @var SoundexGeneratorInterface
     */
    protected $soundexGenerator;
    
    protected $soundexList = [];
    
    /**
     * @inheritDoc
     */
    public function canHandle(LookupRequest $request): bool
    {
        return $request->getType() === LookupRequest::TYPE_AUTOCOMPLETE;
    }
    
    /**
     * @inheritDoc
     */
    public function process(
        iterable $rows,
        LookupRequest $request
    ): array
    {
        $this->input = $request->getInput();
        $this->stopWordList = $request->getStopWordList();
        $this->soundexGenerator = $request->getSoundexGenerator();
        $this->soundexList = [];
        
        $out = [];
        
        $lastWord = $this->getLastWord();
        if (! $lastWord) {
            return [];
        }
        
        $this->soundexList[] = $this->soundexGenerator->generate($lastWord);
        
        foreach ($rows as $row) {
            if (isset($row['content'])) {
                $this->processNodeRow($out, $lastWord, $row);
            } else {
                $isWordCompletion = true;
                $this->processWordRow($out, $lastWord, $row);
            }
        }
        
        if (isset($isWordCompletion)) {
            $out = $this->resortWordCompletionsByPriority($out);
        }
        
        $out = array_values($out);
        
        // The SQL query is allowed to add a "padding" to the list of resolved rows
        // in order to buffer potentially removed stopwords or inflections. So we need to slice
        // away additional rows that would exceed the given limit
        if ($request->getMaxItems() !== null) {
            $out = array_slice($out, 0, $request->getMaxItems());
        }
        
        return $out;
    }
    
    /**
     * Generates the results for a single node row, probably for autocompleting the next word
     *
     * @param   array   $list      The list of results to add the new row to
     * @param   string  $lastWord  The last word in the query string
     * @param   array   $row       The raw database row
     *
     * @return void
     */
    protected function processNodeRow(array &$list, string $lastWord, array $row): void
    {
        preg_match_all('~' . preg_quote($lastWord, '~') . '\\s(.*?)(\\s|$)~ui', $row['content'], $m);
        if (empty($m[1] ?? null)) {
            return;
        }
        
        foreach ($m[1] as $word) {
            if ($this->isSimilarWord($word) || $this->stopWordList->isStopWord(strtolower($word))) {
                continue;
            }
            
            $list[md5($word)] = [
                'content' => rtrim($this->input->string) . ' ' . $word,
                'contentMatch' => '[match]' . rtrim($this->input->string) . '[/match] ' . $word,
            ];
        }
    }
    
    /**
     * Generates the results for a single "word" row, probably for autocompleting the current word
     *
     * @param   array   $list      The list of results to add the new row to
     * @param   string  $lastWord  The last word in the query string
     * @param   array   $row       The raw database row
     *
     * @return void
     */
    protected function processWordRow(array &$list, string $lastWord, array $row): void
    {
        $word = $row['word'];
        if ($this->isSimilarWord($word) || $this->stopWordList->isStopWord(strtolower($word))) {
            return;
        }
        
        // If there are not at least 3 chars more to complete the word
        // we simply ignore it, because it is probably just some kind of inflection...
        $lastWordLength = strlen($lastWord);
        $lengthDiff = strlen($word) - $lastWordLength;
        if ($lengthDiff < 3) {
            return;
        }
        
        $list[md5($word)] = [
            'content' => substr($this->input->string, -strlen($lastWord)) . substr($word, strlen($lastWord)),
            'contentMatch' => '[match]' . substr($this->input->string, -strlen($lastWord)) . '[/match]' . substr($word, strlen($lastWord)),
            'priority' => $row['sort_priority'] + ($row['sort_priority'] / $lastWordLength * $lengthDiff),
        ];
    }
    
    /**
     * Checks if the given word is either phonetically or literary similar to the other words in the list.
     * This allows us to drop similar suggestions
     *
     * @param   string  $word
     *
     * @return bool
     */
    protected function isSimilarWord(string $word): bool
    {
        $soundex = $this->soundexGenerator->generate($word);
        foreach ($this->soundexList as $listWord => $listSoundex) {
            similar_text($soundex, $listSoundex, $p);
            similar_text($word, (string)$listWord, $p2);
            if ($p > 95 || $p2 > 90) {
                return true;
            }
        }
        
        $this->soundexList[$word] = $soundex;
        
        return false;
    }
    
    /**
     * Helper to extract the last word out of the last word list
     *
     * @return string|null
     */
    protected function getLastWord(): ?string
    {
        if (! $this->input->lastWordList) {
            return null;
        }
        
        $parts = explode(' ', $this->input->lastWordList);
        
        return end($parts);
    }
    
    /**
     * As the string length difference is considered to add additional priority for word completions,
     * which is not an easy task in a DB implementation (every db has its own function to do it...)
     * we need to resort the results here.
     *
     * @param   array  $list
     *
     * @return array
     */
    protected function resortWordCompletionsByPriority(array $list): array
    {
        usort($list, static function (array $a, array $b) {
            return $a['priority'] < $b['priority'];
        });
        
        return Arrays::getList($list, ['content', 'contentMatch']) ?? [];
    }
}