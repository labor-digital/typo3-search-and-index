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
use Neunerlei\Inflection\Inflector;

class SearchProcessor implements LookupResultProcessorInterface
{
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
        
        $matchPattern = $this->resolveMatchPattern($request->getInput()->requiredWordLists);
        $fuzzyMatchWords = $this->findSoundexValues(implode(' ', $request->getInput()->requiredWordLists));
        
        $out = [];
        
        foreach ($rows as $row) {
            $content = $row['content'] ?? '';
            $content = $this->findMatchingContent($content, $matchPattern)
                       ?? $this->findMatchingFuzzyContent($content, $fuzzyMatchWords)
                          ?? $this->findMatchingByLookingReallyHard($content, $request->getInput()->requiredWordLists);
            
            $row['contentMatch'] = $this->extractMatchingContent($content, $request->getContentMatchLength());
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
            $patterns[] = preg_quote($list, '~');
            foreach (explode(' ', $list) as $word) {
                $patterns[] = preg_quote($word, '~');
            }
        }
        
        if (empty($patterns)) {
            return '';
        }
        
        return '~(' . implode('|', array_unique($patterns)) . ')~ui';
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
     * @param   string  $content
     * @param   int     $contentMatchLength
     *
     * @return string
     */
    protected function extractMatchingContent(string $content, int $contentMatchLength): string
    {
        if (! str_contains($content, '[match]')) {
            return '';
        }
        
        $pos = max(0, stripos($content, '[match]') - $contentMatchLength / 4);
        $cParts = explode(' ', substr($content, $pos));
        
        // Remove probably broken first word when this is not the start of the string
        if ($pos !== 0) {
            array_shift($cParts);
        }
        
        $c = '';
        while (strlen($c) <= $contentMatchLength && ! empty($cParts)) {
            $c .= rtrim(' ' . trim(array_shift($cParts), ' .,'));
        }
        
        return ($pos === 0 ? '' : '...') . trim($c) . (empty($cParts) ? '' : '...');
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
     * Internal helper to split up a given text into a list of comparable words
     *
     * @param   string  $text
     *
     * @return array
     */
    protected function splitTextIntoWords(string $text): array
    {
        return array_unique(
            array_map('strtolower',
                array_filter(
                    explode(' ',
                        preg_replace('~[^\\w\\s]*~', '', $text)
                    )
                )
            )
        );
    }
}