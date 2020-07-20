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
 * Last modified: 2020.07.17 at 12:57
 */

declare(strict_types=1);


namespace LaborDigital\T3SAI\Indexer;


use LaborDigital\T3SAI\StopWords\StopWordCheckerInterface;
use LaborDigital\Typo3BetterApi\Container\TypoContainer;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

class IndexerSiteLanguage extends SiteLanguage
{
    /**
     * The registered stop word checker for this language
     *
     * @var \LaborDigital\T3SAI\StopWords\StopWordCheckerInterface
     */
    protected $stopWordChecker;
    
    /**
     * @inheritDoc
     */
    public function __construct(
        int $languageId,
        string $locale,
        UriInterface $base,
        array $configuration,
        StopWordCheckerInterface $stopWordChecker
    ) {
        parent::__construct($languageId, $locale, $base, $configuration);
        $this->stopWordChecker = $stopWordChecker;
    }
    
    /**
     * Allows you to override the stop word checker for this language
     *
     * @param   \LaborDigital\T3SAI\StopWords\StopWordCheckerInterface  $stopWordChecker
     *
     * @return IndexerSiteLanguage
     */
    public function setStopWordChecker(StopWordCheckerInterface $stopWordChecker): self
    {
        $this->stopWordChecker = $stopWordChecker;
        
        return $this;
    }
    
    /**
     * Returns the selected stop word checker for this language
     *
     * @return \LaborDigital\T3SAI\StopWords\StopWordCheckerInterface
     */
    public function getStopWordChecker(): StopWordCheckerInterface
    {
        return $this->stopWordChecker;
    }
    
    /**
     * Factory to convert a site language object into a index language object
     *
     * @param   SiteLanguage                $language          The language to convert
     * @param   StopWordCheckerInterface[]  $stopWordCheckers  The list of possible stop word checkers to select one from
     *
     * @return \LaborDigital\T3SAI\Indexer\IndexerSiteLanguage
     */
    public static function makeInstance(SiteLanguage $language, array $stopWordCheckers): self
    {
        // Select stop word checker
        $stopWordChecker = $stopWordCheckers['null'];
        if (isset($stopWordCheckers[$language->getTwoLetterIsoCode()])) {
            $stopWordChecker = $stopWordCheckers[$language->getTwoLetterIsoCode()];
        } elseif ($stopWordCheckers['default']) {
            $stopWordChecker = $stopWordCheckers['default'];
        }
        
        // Create a new instance of myself
        $self = TypoContainer::getInstance()->get(static::class, [
            'args' => [
                $language->getLanguageId(),
                $language->getLocale(),
                $language->getBase(),
                $language->configuration,
                $stopWordChecker,
            ],
        ]);
        foreach (get_object_vars($language) as $k => $v) {
            $self->$k = $v;
        }
        
        return $self;
    }
    
}
