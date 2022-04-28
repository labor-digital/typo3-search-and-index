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
 * Last modified: 2022.04.21 at 10:47
 */

declare(strict_types=1);


namespace LaborDigital\T3sai\Core\Translation\Tags;


use LaborDigital\T3ba\Tool\Translation\Translator;
use LaborDigital\T3sai\Core\Lookup\Lexer\ParsedInput;
use Neunerlei\Inflection\Inflector;

class TagTranslationProvider
{
    
    /**
     * @var \LaborDigital\T3ba\Tool\Translation\Translator
     */
    protected $translator;
    
    public function __construct(Translator $translator)
    {
        $this->translator = $translator;
    }
    
    /**
     * Tries to resolve potentially translated tags in the given input object into the internal tag names
     *
     * @param   array                                              $domainConfig
     * @param   \LaborDigital\T3sai\Core\Lookup\Lexer\ParsedInput  $input
     *
     * @return void
     */
    public function resolveTranslationsInParsedInput(array $domainConfig, ParsedInput $input): void
    {
        // Ignore if there is nothing to do
        if (empty($input->requiredTags) && empty($input->deniedTags)) {
            return;
        }
        
        $map = $this->getInputTagMap($domainConfig);
        
        $input->requiredTags = $this->translateTagList($input->requiredTags, $map);
        $input->deniedTags = $this->translateTagList($input->deniedTags, $map);
    }
    
    /**
     * Returns the translated label for the provided tag
     *
     * @param   array   $domainConfig
     * @param   string  $tag
     *
     * @return string
     */
    public function getTranslatedLabel(array $domainConfig, string $tag): string
    {
        return $this->translateLabel($domainConfig, $tag, 'label')
               ?? Inflector::toHuman($tag);
    }
    
    /**
     * Returns the provided input (@) label for the provided tag
     *
     * @param   array   $domainConfig
     * @param   string  $tag
     *
     * @return string
     */
    public function getTranslatedInputLabel(array $domainConfig, string $tag): string
    {
        return $this->translateLabel($domainConfig, $tag, 'inputLabel') ?? $tag;
    }
    
    /**
     * Performs the reverse operation of getTranslatedInputLabel() by translating a localized input label back to a tag
     *
     * @param   array   $domainConfig
     * @param   string  $inputLabel
     *
     * @return string
     */
    public function getTagByTranslatedInputLabel(array $domainConfig, string $inputLabel): string
    {
        $map = $this->getInputTagMap($domainConfig);
        
        return $this->translateTagList([$inputLabel], $map)[0];
    }
    
    /**
     * Resolves a map of TagTranslations => actualTag to resolve incoming, translated tags with.
     *
     * @param   array  $domainConfig
     *
     * @return array
     */
    protected function getInputTagMap(array $domainConfig): array
    {
        $map = [];
        foreach ($domainConfig['tagTranslations'] ?? [] as $tag => $config) {
            $map[$tag] = strtolower($this->translator->translate($config['inputLabel'] ?? ''));
        }
        
        return array_flip($map);
    }
    
    /**
     * Translates the given list of tags by the provided tag map
     *
     * @param   array  $list    The numeric list of tags to be translated
     * @param   array  $tagMap  A translation map where: translatedTag => actual tag
     *
     * @return array
     */
    protected function translateTagList(array $list, array $tagMap): array
    {
        $out = [];
        foreach ($list as $tag) {
            $out[] = $tagMap[strtolower($tag)] ?? $tag;
        }
        
        return array_unique($out);
    }
    
    protected function translateLabel(array $domainConfig, string $tag, string $type): ?string
    {
        $label = $domainConfig['tagTranslations'][$tag][$type] ?? null;
        
        if (! $label) {
            return null;
        }
        
        return $this->translator->translate($label);
    }
}