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
 * Last modified: 2022.04.14 at 16:27
 */

declare(strict_types=1);


namespace LaborDigital\T3sai\ExtConfigHandler\Domain\Internal;


use InvalidArgumentException;
use LaborDigital\T3ba\Core\Di\NoDiInterface;

class LanguageClassList implements NoDiInterface
{
    /**
     * Defines the name of the interface which is required for a class to be part of this list
     *
     * @var string
     */
    protected $requiredInterface;
    
    /**
     * The list of registered classes
     *
     * @var array
     */
    protected $classes = [];
    
    public function __construct(string $requiredInterface)
    {
        $this->requiredInterface = $requiredInterface;
    }
    
    /**
     * Returns the name of the interface which is required for classes to implement when
     * they are added to the list.
     *
     * @return string
     */
    public function getRequiredInterface(): string
    {
        return $this->requiredInterface;
    }
    
    /**
     * Adds a new class name to the list.
     * Note: The class must implement {@link getRequiredInterface()}!
     * Note: You may use "default" as language code to match all, not explicitly configured language codes
     *
     * @param   string  $languageCode  A two-letter ISO language code
     * @param   string  $className     The name of the class to register
     *
     * @return $this
     */
    public function add(string $languageCode, string $className): self
    {
        if ($languageCode !== 'default' && strlen($languageCode) !== 2) {
            throw new InvalidArgumentException('Invalid language code: "' . $languageCode . '" given!');
        }
        
        if (! class_exists($className)) {
            throw new InvalidArgumentException('The given class: "' . $className . '" does not exist');
        }
        
        if (! in_array($this->requiredInterface, class_implements($className), true)) {
            throw new InvalidArgumentException(
                'The given class: "' . $className .
                '" does not implement the required interface: "' . $this->requiredInterface . '"');
        }
        
        $this->classes[strtolower($languageCode)] = $className;
        
        return $this;
    }
    
    /**
     * The same as "add" but allows to provide an array of class names instead
     *
     * @param   array  $classNames  An array of class names to be added to the list
     *                              Either as list of class names or as languageCode => ClassName
     *
     * @return $this
     */
    public function addMultiple(array $classNames): self
    {
        foreach ($classNames as $a => $b) {
            $this->add((string)$a, $b);
        }
        
        return $this;
    }
    
    /**
     * Removes a previously registered class from the list
     *
     * @param   string  $className  The name of the class to remove
     *
     * @return $this
     */
    public function remove(string $className): self
    {
        $this->classes = array_filter($this->classes, static function ($v) use ($className) {
            return $className !== $v;
        });
        
        return $this;
    }
    
    /**
     * Removes a previously registered class for a given language code from the list
     *
     * @param   string  $languageCode  The two-letter code of the language to remove
     *
     * @return $this*
     */
    public function removeLanguage(string $languageCode): self
    {
        unset($this->classes[strtolower($languageCode)]);
        
        return $this;
    }
    
    /**
     * Returns all registered classes in this list
     *
     * @return array
     */
    public function getAll(): array
    {
        return $this->classes;
    }
    
    /**
     * Removes all previously registered from the list
     *
     * @return $this
     */
    public function clear(): self
    {
        $this->classes = [];
        
        return $this;
    }
}