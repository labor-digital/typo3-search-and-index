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
 * Last modified: 2022.04.20 at 19:14
 */

declare(strict_types=1);


namespace LaborDigital\T3sai\ViewHelpers\Format;


use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithContentArgumentAndRenderStatic;

class ContentMatchViewHelper extends AbstractViewHelper
{
    use CompileWithContentArgumentAndRenderStatic;
    
    protected $escapeOutput = false;
    
    /**
     * @inheritDoc
     */
    public function initializeArguments()
    {
        $this->registerArgument('content', 'string', 'The "contentMatch" string to be formatted to html');
    }
    
    /**
     * @inheritDoc
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $content = $arguments['content'] ?? $renderChildrenClosure();
        
        return preg_replace('~\\[match](.*?)\\[/match]~', '<strong class="t3saiMatch">$1</strong>', $content);
    }
    
    
}