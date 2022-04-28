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
 * Last modified: 2022.04.27 at 09:59
 */

declare(strict_types=1);


namespace LaborDigital\T3sai\Core\Indexer\Dictionary;

/**
 * Nerf words are a list of words that occur so often, in so many nodes, that they can be considered irrelevant.
 * Therefore, we can "nerf" down their word priority pretty hard
 */
class NerfWordList
{
    protected const KEY_NODE_COUNT = 0;
    protected const KEY_OCCURRENCES = 1;
    protected const KEY_WORD_LIST = 2;
    
    protected $words = [];
    
    /**
     * Imports the list of words to learn about.
     *
     * @param   array  $wordsList  This argument must be the output of {@link \LaborDigital\T3sai\Core\Indexer\Node\Converter\TextConverter::convertText()}
     *                             to tell the list about new words
     *
     * @return void
     */
    public function learnWords(array $wordsList): void
    {
        foreach ($wordsList as $word => $data) {
            // Key- and stop-words are simply ignored
            if ($data['isStopWord'] || $data['isKeyword']) {
                continue;
            }
            
            if (isset($this->words[$word])) {
                $this->words[$word][static::KEY_NODE_COUNT]++;
                $this->words[$word][static::KEY_OCCURRENCES] += $data['occurrences'];
            } else {
                $this->words[$word] = [
                    static::KEY_NODE_COUNT => 1,
                    static::KEY_OCCURRENCES => $data['occurrences'],
                    static::KEY_WORD_LIST => [$word],
                ];
            }
        }
    }
    
    /**
     * Removes all words the list has learned about
     *
     * @return void
     */
    public function flush(): void
    {
        $this->words = [];
    }
    
    /**
     * Returns the list of all nerf words that have been determined
     *
     * @return iterable
     */
    public function getAll(): iterable
    {
        $chunks = $this->combineSimilarChunks($this->words);
        $medianOccurrences = $this->calculateMedianOfOccurrences($chunks);
        $medianNodes = $this->calculateMedianOfNodes($chunks);
        
        foreach ($chunks as $chunk) {
            if (! $this->isNerfWordChunk($chunk, $medianOccurrences, $medianNodes)) {
                continue;
            }
            
            yield from $chunk[static::KEY_WORD_LIST];
        }
    }
    
    protected function combineSimilarChunks(array $chunks): array
    {
        $combined = [];
        
        foreach ($chunks as $id => $data) {
            $mergeId = $this->getChunkMergeId($combined, $id);
            
            if (! $mergeId || ! isset($combined[$mergeId])) {
                $combined[$id] = $data;
            } else {
                $combined[$mergeId][static::KEY_NODE_COUNT] += $data[static::KEY_NODE_COUNT];
                $combined[$mergeId][static::KEY_OCCURRENCES] += $data[static::KEY_OCCURRENCES];
                $combined[$mergeId][static::KEY_WORD_LIST] = array_merge($combined[$mergeId][static::KEY_WORD_LIST], $data[static::KEY_WORD_LIST]);
            }
        }
        
        return array_values($combined);
    }
    
    protected function getChunkMergeId(array $combined, string $id): ?string
    {
        $candidates = [];
        $length = mb_strlen($id);
        
        if (mb_strlen($id) <= 3) {
            return null;
        }
        
        foreach (($combined ?? []) as $_id => $_) {
            similar_text($_id, $id, $p1);
            if (($length > 5 && $p1 > 93) || $p1 > 98) {
                $candidates[$_id] = $p1;
            }
        }
        
        if (empty($candidates)) {
            return null;
        }
        
        asort($candidates);
        
        return key($candidates);
    }
    
    protected function calculateMedianOfOccurrences(array $chunks): float
    {
        return $this->calculateMedian(
            array_column($chunks, static::KEY_OCCURRENCES)
        );
    }
    
    protected function calculateMedianOfNodes(array $chunks): float
    {
        return $this->calculateMedian(
            array_column($chunks, static::KEY_NODE_COUNT)
        );
    }
    
    /**
     * Calculates the median value of the list of given values
     *
     * @param   array  $values
     *
     * @return float
     */
    protected function calculateMedian(array $values): float
    {
        sort($values);
        $count = count($values);
        $middle = floor(($count - 1) / 2);
        if ($count % 2) {
            $median = $values[$middle];
        } else {
            $low = $values[$middle];
            $high = $values[$middle + 1];
            $median = (($low + $high) / 2);
        }
        
        return $median;
    }
    
    protected function isNerfWordChunk(array $chunk, float $medianOccurrences, float $medianNodes): bool
    {
        return $chunk[static::KEY_OCCURRENCES] > $medianOccurrences * 5
               && $chunk[static::KEY_NODE_COUNT] > $medianNodes * 8;
    }
}