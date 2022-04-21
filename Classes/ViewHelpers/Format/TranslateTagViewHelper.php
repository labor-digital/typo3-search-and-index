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
 * Last modified: 2022.04.21 at 14:35
 */

declare(strict_types=1);


namespace LaborDigital\T3sai\ViewHelpers\Format;


use LaborDigital\T3sai\Core\Translation\Tags\TagTranslationProvider;
use LaborDigital\T3sai\Domain\Repository\DomainConfigRepository;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class TranslateTagViewHelper extends AbstractViewHelper
{
    /**
     * @var \LaborDigital\T3sai\Core\Translation\Tags\TagTranslationProvider
     */
    protected $translationProvider;
    /**
     * @var \LaborDigital\T3sai\Domain\Repository\DomainConfigRepository
     */
    protected $configRepository;
    
    public function __construct(TagTranslationProvider $translationProvider, DomainConfigRepository $configRepository)
    {
        $this->translationProvider = $translationProvider;
        $this->configRepository = $configRepository;
    }
    
    /**
     * @inheritDoc
     */
    public function initializeArguments(): void
    {
        $this->registerArgument('tag', 'string', 'The key of the tag to be translated', true);
        $this->registerArgument('domainIdentifier', 'string', 'The search domain identifier for which the tag should be translated', true);
        $this->registerArgument('asInput', 'boolean', 'Returns the tag translation for the input label', false, false);
    }
    
    public function render(): string
    {
        $domainConfig = $this->configRepository->findDomainConfig($this->arguments['domainIdentifier']) ?? [];
        
        if ($this->arguments['asInput']) {
            return $this->translationProvider->getTranslatedInputLabel($domainConfig, $this->arguments['tag']);
        }
        
        return $this->translationProvider->getTranslatedLabel($domainConfig, $this->arguments['tag']);
    }
}