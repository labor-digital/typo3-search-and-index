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
 * Last modified: 2020.07.16 at 21:47
 */

declare(strict_types=1);


namespace LaborDigital\T3SAI\Indexer\Queue;


use LaborDigital\T3SAI\Exception\Indexer\InvalidIndexerConfigException;
use LaborDigital\T3SAI\Exception\Indexer\InvalidStopWordCheckerException;
use LaborDigital\T3SAI\Indexer\IndexerContext;
use LaborDigital\T3SAI\Indexer\IndexerSiteLanguage;
use LaborDigital\T3SAI\StopWords\EnglishStopWords;
use LaborDigital\T3SAI\StopWords\GermanStopWords;
use LaborDigital\T3SAI\StopWords\NullStopWords;
use LaborDigital\T3SAI\StopWords\StopWordCheckerInterface;
use Throwable;

class LanguageQueue
{
    
    /**
     * The indexer context object
     *
     * @var \LaborDigital\T3SAI\Indexer\IndexerContext
     */
    protected $context;
    
    /**
     * LanguageQueue constructor.
     *
     * @param   \LaborDigital\T3SAI\Indexer\IndexerSiteLanguage[]  $languages
     * @param   \LaborDigital\T3SAI\Indexer\IndexerContext         $context
     */
    public function __construct(IndexerContext $context)
    {
        $this->context = $context;
    }
    
    /**
     * Iterates over all registered languages and executes the given callback for them
     *
     * @param   callable  $callback
     */
    public function process(callable $callback): void
    {
        foreach ($this->makeLanguageList() as $language) {
            // Store the language
            $this->context->setLanguage($language);
            
            try {
                // Run the callback in the correct language environment
                $this->context->getEnvironmentSimulator()->runWithEnvironment([
                    'language' => $language,
                ], $callback);
            } catch (Throwable $exception) {
                // Catch errors
                $this->context->logError(
                    'Failed to process a language! ' .
                    'Domain: ' . $this->context->getDomainKey() .
                    ', Language: ' . $this->context->getLanguage()->getTitle() .
                    ', Error:' . $exception->getMessage() .
                    ', File: ' . $exception->getFile() . ' (' . $exception->getLine() . ')',
                    [$exception->getTraceAsString()]
                );
            } finally {
                // Reset the language
                $this->context->setLanguage(null);
            }
            
        }
    }
    
    /**
     * Builds the list of all registered indexer language objects
     *
     * @return IndexerSiteLanguage[]
     * @throws \LaborDigital\T3SAI\Exception\Indexer\InvalidIndexerConfigException
     */
    protected function makeLanguageList(): array
    {
        /** @var IndexerSiteLanguage[] $languages */
        $languages        = [];
        $stopWordCheckers = $this->makeStopWordCheckers();
        $domainConfig     = $this->context->getDomainConfig();
        foreach ($this->context->getTypoContext()->Language()->getAllFrontendLanguages() as $lang) {
            if (! empty($domainConfig->getAllowedLanguages()) &&
                ! in_array($lang->getTwoLetterIsoCode(), $domainConfig->getAllowedLanguages(), true)) {
                continue;
            }
            $languages[] = IndexerSiteLanguage::makeInstance($lang, $stopWordCheckers);
        }
        
        // Fail if there are no languages
        if (empty($languages)) {
            throw new InvalidIndexerConfigException(
                'The search domain: ' . $this->context->getDomainKey() . ' has no registered languages!'
            );
        }
        
        return $languages;
    }
    
    /**
     * Returns the list of the stop word checker instances
     *
     * @return StopWordCheckerInterface[]
     * @throws \LaborDigital\T3SAI\Exception\Indexer\InvalidStopWordCheckerException
     */
    protected function makeStopWordCheckers(): array
    {
        $container        = $this->context->getContainer();
        $stopWordCheckers = [
            'en'   => $container->getWithoutDi(EnglishStopWords::class),
            'de'   => $container->getWithoutDi(GermanStopWords::class),
            'null' => $container->getWithoutDi(NullStopWords::class),
        ];
        
        foreach ($this->context->getDomainConfig()->getStopWordCheckers() as $lang => $className) {
            $stopWordCheckers[$lang] = $container->get($className);
            if (! $stopWordCheckers[$lang] instanceof StopWordCheckerInterface) {
                throw new InvalidStopWordCheckerException(
                    'The given stop word checker $className does not implement the required interface!');
            }
        }
        
        return $stopWordCheckers;
    }
}
