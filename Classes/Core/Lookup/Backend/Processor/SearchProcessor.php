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
 * Last modified: 2022.04.19 at 18:37
 */

declare(strict_types=1);


namespace LaborDigital\T3sai\Core\Lookup\Backend\Processor;


use LaborDigital\T3ba\Tool\OddsAndEnds\SerializerUtil;
use LaborDigital\T3sai\Core\Lookup\Request\LookupRequest;
use LaborDigital\T3sai\Core\Soundex\SoundexGeneratorInterface;
use Neunerlei\Arrays\Arrays;
use Neunerlei\Inflection\Inflector;

class SearchProcessor implements LookupResultProcessorInterface
{
    use ProcessorUtilTrait;
    
    /**
     * @var SoundexGeneratorInterface
     */
    protected $soundexGenerator;
    
    /**
     * The list of currently resolved soundex values to save performance when using fuzzy search
     *
     * @var array
     */
    protected $soundexCache = [];
    
    /**
     * @inheritDoc
     */
    public function canHandle(LookupRequest $request): bool
    {
        return $request->getType() === LookupRequest::TYPE_SEARCH;
    }
    
    /**
     * @inheritDoc
     */
    public function process(
        iterable $rows,
        LookupRequest $request
    ): array
    {
        $this->soundexGenerator = $request->getSoundexGenerator();
        $this->soundexCache = [];
        
        $matchWordLists = $request->getInput()->requiredWordLists;
        $matchPattern = $this->resolveMatchPattern($matchWordLists);
        $fuzzyMatchWords = $this->findSoundexValues(implode(' ', $matchWordLists));
        
        $out = [];
        
        foreach ($rows as $row) {
            $localMatchPattern
                = $this->resolveLocalMatchPattern($row['distinct_words'] ?? null, $matchWordLists)
                  ?? $matchPattern;
            $content = $row['content'] ?? '';
            $content = $this->findMatchingContent($content, $localMatchPattern)
                       ?? $this->findMatchingFuzzyContent($content, $fuzzyMatchWords)
                          ?? $this->findMatchingByLookingReallyHard($content, $matchWordLists);
            
            $row['contentMatch'] = $this->extractMatchingContent(
                $content,
                $request->getContentMatchLength(),
                $request->getDomainConfig()['contentSeparator'] ?? null
            );
            $row['metaData'] = $this->unpackMetaData($row['meta_data'] ?? null);
            $out[] = $row;
        }
        
        $this->soundexCache = [];
        
        return $out;
    }
    
    /**
     * Resolves a match pattern for all words inside the given word lists
     *
     * @param   array  $matchWordLists
     *
     * @return string
     */
    protected function resolveMatchPattern(array $matchWordLists): string
    {
        $patterns = [];
        
        foreach ($matchWordLists as $list) {
            $patterns[] = preg_quote((string)$list, '~');
            foreach (explode(' ', $list) as $word) {
                $patterns[] = preg_quote($word, '~');
            }
        }
        
        if (empty($patterns)) {
            return '';
        }
        
        return '~(' . implode('|', array_unique($patterns)) . ')~ui';
    }
    
    protected function resolveLocalMatchPattern(?string $distinctWords, array $matchWordLists): ?string
    {
        if (! $distinctWords) {
            return null;
        }
        
        $finds = Arrays::makeFromStringList($distinctWords);
        if (empty($finds)) {
            return null;
        }
        
        return $this->resolveMatchPattern(array_merge($finds, $matchWordLists));
    }
    
    /**
     * Applies the "[match]" tags around the content findable by the given $matchPattern
     *
     * @param   string  $content
     * @param   string  $matchPattern
     *
     * @return string|null
     */
    protected function findMatchingContent(string $content, string $matchPattern): ?string
    {
        if (empty($matchPattern)) {
            return null;
        }
        
        $modifiedContent = preg_replace($matchPattern, '[match]$1[/match]', $content, -1, $count);
        
        if ($count !== 0) {
            return $modifiedContent;
        }
        
        return null;
    }
    
    /**
     * Similar to findMatchingContent but, uses soundex comparison to find similar words
     *
     * @param   string  $content
     * @param   array   $matchWords
     *
     * @return string
     */
    protected function findMatchingFuzzyContent(string $content, array $matchWords): ?string
    {
        $contentWords = $this->findSoundexValues($content);
        
        $searchWords = [];
        foreach ($contentWords as $contentWord => $soundex) {
            if (in_array($soundex, $matchWords, true)) {
                $searchWords[] = $contentWord;
            }
        }
        
        $fuzzyPattern = $this->resolveMatchPattern(array_unique($searchWords));
        
        return $this->findMatchingContent($content, $fuzzyPattern);
    }
    
    /**
     * This is an expensive, last ditch effort to find at least some words that look similar in our
     * match value by comparing the words through PHP
     *
     * @param   string  $content
     * @param   array   $matchWords
     *
     * @return string
     */
    protected function findMatchingByLookingReallyHard(string $content, array $matchWords): string
    {
        $compMatchWords = array_map([Inflector::class, 'toComparable'], $matchWords);
        $searchWords = [];
        
        foreach ($this->splitTextIntoWords($content) as $word) {
            $compWord = Inflector::toComparable($word);
            foreach ($compMatchWords as $compMatchWord) {
                similar_text($compWord, $compMatchWord, $p);
                if ($p > 85) {
                    $searchWords[] = $word;
                }
            }
        }
        
        $pattern = $this->resolveMatchPattern($searchWords);
        
        return $this->findMatchingContent($content, $pattern) ?? '';
    }
    
    /**
     * Generates an array of soundex values of all words in the given text
     *
     * @param   string  $text
     *
     * @return array
     */
    protected function findSoundexValues(string $text): array
    {
        $out = [];
        foreach ($this->splitTextIntoWords($text) as $word) {
            if (isset($this->soundexCache[$word])) {
                $out[$word] = $this->soundexCache[$word];
                continue;
            }
            
            $this->soundexCache[$word] = $out[$word] = $this->soundexGenerator->generate($word);
        }
        
        return $out;
    }
    
    /**
     * Extracts the first possible content match from the given content string
     *
     * @param   string       $content
     * @param   int          $contentMatchLength
     * @param   string|null  $contentSeparator
     *
     * @return string
     * @todo $contentSeparator should be required in the next release
     */
    protected function extractMatchingContent(
        string $content,
        int $contentMatchLength,
        ?string $contentSeparator = null
    ): string
    {
        if (! str_contains($content, '[match]')) {
            return '';
        }
        
        // Split up the text into "blocks" of strings "before" the match, the actual "match",
        // and the text "after" the match until the next match....
        $lastBlock = '';
        $blocksByMatch = [];
        foreach (explode('[match]', $content) as $block) {
            $matchEndPos = mb_strpos($block, '[/match]');
            if (! $matchEndPos) {
                $lastBlock = $block;
                continue;
            }
            
            $match = mb_substr($block, 0, $matchEndPos);
            $blocksByMatch[Inflector::toComparable($match)][] = [
                'before' => $lastBlock,
                'match' => $match,
                'after' => mb_substr($block, $matchEndPos + 8),
            ];
            $lastBlock = $block;
        }
        unset($lastBlock);
        
        $matchedContent = [];
        $matches = array_keys($blocksByMatch);
        $matchIdx = 0;
        $loops = 0;
        
        // In order to distribute the list of "finds" evenly between the matches
        // we iterate the comparable matches and iterate them one by one. This might
        // break the order inside the content, but helps the usability
        $matchLength = 0;
        while ($matchLength < $contentMatchLength) {
            if ($loops++ > 20) {
                break;
            }
            
            $match = $matches[$matchIdx];
            $block = array_shift($blocksByMatch[$match]);
            if (! $block) {
                continue;
            }
            
            $blockContent = $this->generateMatchForBlock($block);
            $matchedContent[] = $blockContent;
            $matchLength += mb_strlen($blockContent);
            if (++$matchIdx >= count($matches)) {
                $matchIdx = 0;
            }
        }
        
        $result = implode($contentSeparator ?? ' [...] ', $matchedContent);
        $result = trim(preg_replace('~^\[\.\.\.]|\[\.\.\.]$|\[\.\.\.]\\s+\[\.\.\.]~', '', $result));
        
        return '... ' . $result . ' ...';
    }
    
    /**
     * Helper to deserialize the meta-data stored in the processed database row
     *
     * @param   string|null  $metaData
     *
     * @return array
     */
    protected function unpackMetaData(?string $metaData): array
    {
        if (empty($metaData)) {
            return [];
        }
        
        return SerializerUtil::unserializeJson($metaData);
    }
    
    /**
     * Internal helper for extractMatchingContent to convert a match block into a match string.
     *
     * @param   array  $block
     *
     * @return string
     */
    protected function generateMatchForBlock(array $block): string
    {
        $pattern = '~\[/match]|\[\.\.\.] $~';
        $wordsBeforeMatch = array_slice($this->splitTextIntoWords(
            trim(preg_replace($pattern, '', $block['before'])), true), -5);
        $spaceBeforeMatch = str_ends_with($block['before'], ' ');
        $wordBeforeMatch = array_pop($wordsBeforeMatch);
        $wordsAfterMatch = array_slice($this->splitTextIntoWords(
            trim(preg_replace($pattern, '', $block['after'])), true), 0, 5);
        $spaceAfterMatch = str_starts_with($block['after'], ' ');
        $wordAfterMatch = array_shift($wordsAfterMatch);
        
        return implode(' ', array_merge(
            $wordsBeforeMatch,
            [
                $wordBeforeMatch . ($spaceBeforeMatch ? ' ' : '') .
                '[match]' . $block['match'] . '[/match]' .
                ($spaceAfterMatch ? ' ' : '') . $wordAfterMatch,
            ],
            $wordsAfterMatch
        ));
    }
}