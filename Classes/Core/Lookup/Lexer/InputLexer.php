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
 * Last modified: 2022.04.13 at 15:18
 */

declare(strict_types=1);


namespace LaborDigital\T3sai\Core\Lookup\Lexer;


use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class InputLexer implements SingletonInterface
{
    protected const TOKEN_NEGATE = '-';
    protected const TOKEN_QUOTE = '"';
    protected const TOKEN_ESCAPE = '\\';
    protected const TOKEN_SPACE = ' ';
    
    protected const MODIFIER_TAG = '@';
    
    protected $remainingQuotes = 0;
    protected $currentGroup;
    protected $currentGroupModifier;
    protected $currentGroupIsNegated = false;
    protected $isEscaped = false;
    protected $detectModifier = false;
    protected $isInQuote = false;
    
    /**
     * Receives a query string and parses it by extracting tags and extracting word lists from it
     *
     * @param   string  $input
     *
     * @return \LaborDigital\T3sai\Core\Lookup\Lexer\ParsedInput
     */
    public function parse(string $input): ParsedInput
    {
        $this->reset();
        
        $inputTrimmed = trim($input);
        $queryLength = strlen($inputTrimmed);
        
        // We use the remaining quotes count to ensure the last list of words, containing a quote stays together
        $this->remainingQuotes = substr_count($inputTrimmed, static::TOKEN_QUOTE);
        
        $result = $this->initializeResult($input);
        
        if (empty($inputTrimmed)) {
            return $result;
        }
        
        for ($i = 0; $i < $queryLength; $i++) {
            $this->processChar($inputTrimmed[$i], $result);
        }
        
        $this->finishCurrentGroup($result);
        
        return $result;
    }
    
    /**
     * Creates a new, empty result object
     *
     * @param   string  $queryString
     *
     * @return \LaborDigital\T3sai\Core\Lookup\Lexer\ParsedInput
     */
    protected function initializeResult(string $queryString): ParsedInput
    {
        $r = GeneralUtility::makeInstance(ParsedInput::class);
        $r->string = $queryString;
        
        return $r;
    }
    
    /**
     * Processes a single character of the query string based on the lexer grammar
     *
     * @param   string                                             $char
     * @param   \LaborDigital\T3sai\Core\Lookup\Lexer\ParsedInput  $result
     *
     * @return void
     */
    protected function processChar(string $char, ParsedInput $result): void
    {
        switch ($char) {
            case static::TOKEN_ESCAPE:
                $this->isEscaped = ! $this->isEscaped;
                
                return;
            case static::TOKEN_SPACE:
                $this->detectModifier = true;
                
                if ((! empty($this->currentGroupModifier) || $this->currentGroupIsNegated) && ! $this->isInQuote) {
                    $this->finishCurrentGroup($result);
                    
                    return;
                }
                
                break;
            case static::TOKEN_NEGATE:
                if ($this->isInQuote || ! $this->detectModifier) {
                    break;
                }
                
                if ($this->isEscaped) {
                    $this->isEscaped = false;
                    break;
                }
                
                $this->finishCurrentGroup($result);
                $this->currentGroupIsNegated = true;
                
                return;
            case static::TOKEN_QUOTE:
                if ($this->isEscaped) {
                    $this->isEscaped = false;
                    $this->remainingQuotes--;
                    break;
                }
                
                if ($this->isInQuote) {
                    $this->remainingQuotes--;
                    $this->finishCurrentGroup($result);
                    
                    return;
                }
                
                if ($this->remainingQuotes === 1) {
                    break;
                }
                
                $this->isInQuote = true;
                $this->remainingQuotes--;
                
                return;
            case static::MODIFIER_TAG:
                if ($this->isInQuote || ! $this->detectModifier) {
                    break;
                }
                
                if ($this->isEscaped) {
                    $this->isEscaped = false;
                    break;
                }
                
                if (! empty($this->currentGroup)) {
                    $this->finishCurrentGroup($result);
                }
                
                $this->currentGroupModifier = $char;
                
                return;
            default:
                $this->detectModifier = false;
                $this->isEscaped = false;
        }
        
        $this->currentGroup .= $char;
    }
    
    /**
     * Transfers the current group into the result object, before resetting the local properties
     *
     * @param   \LaborDigital\T3sai\Core\Lookup\Lexer\ParsedInput  $result
     *
     * @return void
     */
    protected function finishCurrentGroup(ParsedInput $result): void
    {
        if (! empty($this->currentGroup)) {
            $value = trim($this->currentGroup);
            
            if ($this->currentGroupModifier) {
                $result->lastWordList = null;
                if ($this->currentGroupIsNegated) {
                    $result->deniedTags[] = $value;
                } else {
                    $result->requiredTags[] = $value;
                }
            } else {
                $result->lastWordList = $value;
                if ($this->currentGroupIsNegated) {
                    $result->deniedWordLists[] = $value;
                } else {
                    $result->requiredWordLists[] = $value;
                }
            }
        }
        
        $this->isInQuote = false;
        $this->isEscaped = false;
        $this->currentGroup = null;
        $this->currentGroupIsNegated = false;
        $this->currentGroupModifier = null;
    }
    
    /**
     * Resets all internal properties to the initial state
     *
     * @return void
     */
    protected function reset(): void
    {
        $this->remainingQuotes = 0;
        $this->detectModifier = true;
        $this->isEscaped = false;
        $this->isInQuote = false;
        $this->currentGroup = null;
        $this->currentGroupIsNegated = false;
        $this->currentGroupModifier = null;
    }
}