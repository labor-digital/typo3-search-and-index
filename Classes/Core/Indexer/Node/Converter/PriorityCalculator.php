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
 * Last modified: 2022.04.12 at 14:14
 */

declare(strict_types=1);


namespace LaborDigital\T3sai\Core\Indexer\Node\Converter;


use DateTime;

class PriorityCalculator
{
    /**
     * Calculates the node base priority by combining the given priority,
     * with the calculated priority of the word list and takes the age of the node into account as well.
     *
     * @param   \DateTime  $timestamp
     * @param   float      $nodePriority
     * @param   array      $wordList
     *
     * @return float
     */
    public function calculateNodePriority(DateTime $timestamp, float $nodePriority, array $wordList): float
    {
        // Modify priority based on content
        $wordPriorities = array_column($wordList, 'priority');
        $wordPriority = array_sum($wordPriorities) / max(1, count($wordPriorities));
        $wordPriority = (min($wordPriority, $nodePriority) / max($nodePriority, $wordPriority, 1)) * 100;
        
        $tmp = $nodePriority + (min(100, $wordPriority) / 3);
        $nodePriority = (float)($nodePriority > 100 ? $tmp : min(100, $tmp));
        
        // Modify content based on timestamp
        $dateDiff = $timestamp->diff(new DateTime('now'));
        $nodePriority -= min(40, abs($nodePriority) * $dateDiff->days / 7 / 2 / 50);
        
        return (float)max(-20, $nodePriority);
    }
    
    /**
     * Receives the output of {@link \LaborDigital\T3sai\Core\Indexer\Node\Converter\WordExtractor::extractWords()}
     * and calculates the priority for each word and returns the updated array, with the "priority" correctly calcualted
     *
     * @param   array  $wordList
     *
     * @return array
     */
    public function calculateWordPriority(array $wordList): array
    {
        $maxPriority = 0;
        foreach ($wordList as $word => $data) {
            // Don't calculate stop words
            if ($data['isStopWord']) {
                $wordList[$word]['priority'] = 0;
                continue;
            }
            
            $basePriority = max($data['priority'], 10);
            $priority = $basePriority;
            // Add additional priority based on the word length (+1% points per character)
            $priority += ($basePriority * 0.01 * mb_strlen($word));
            // Add additional priority based on the occurrences of the word (+10% per occurrence)
            $priority += ($basePriority * 0.1 * $data['occurrences']);
            // Add additional priority for each text part the word occurred in (+5% per occurrence)
            $priority += ($basePriority * 0.05 * $data['occurrencesInTexts']);
            
            // Detect max priority
            if ($priority > $maxPriority) {
                $maxPriority = $priority;
            }
            
            $wordList[$word]['priority'] = $priority;
        }
        
        // Unify the priorities to a range between 0 - 100
        $mod = $maxPriority / 100;
        if ($maxPriority !== 0) {
            foreach ($wordList as $word => $data) {
                $wordList[$word]['priority'] = ($data['priority'] / $maxPriority) * $mod * 100;
                
                if ($data['isKeyword']) {
                    $wordList[$word]['priority'] += 100;
                }
            }
        }
        
        return $wordList;
    }
}