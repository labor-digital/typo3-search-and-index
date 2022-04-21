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
 * Last modified: 2022.04.11 at 12:29
 */

declare(strict_types=1);


namespace LaborDigital\T3sai\ExtConfigHandler\Domain\Internal;


use InvalidArgumentException;
use LaborDigital\T3ba\Core\Di\NoDiInterface;

class IndexerList implements NoDiInterface
{
    /**
     * Defines the name of the interface which is required for an indexer to be part of this list
     *
     * @var string
     */
    protected $requiredInterface;
    
    /**
     * The list of registered indexer classes
     *
     * @var array
     */
    protected $classes = [];
    
    public function __construct(string $requiredInterface)
    {
        $this->requiredInterface = $requiredInterface;
    }
    
    /**
     * Returns the name of the interface which is required for indexers to implement when
     * they are added to the list.
     *
     * @return string
     */
    public function getRequiredInterface(): string
    {
        return $this->requiredInterface;
    }
    
    /**
     * Adds a new indexer class name to the list.
     * Note: The class must implement {@link getRequiredInterface()}!
     *
     * @param   string      $className  The name of the indexer class to register
     * @param   array|null  $options    Additional options to pass to the indexer instance.
     *                                  Consult the implementation you are registering for a list of supported options.
     *                                  NOTE: All options must be JSON encode-able!
     *
     * @return $this
     */
    public function add(string $className, ?array $options = null): self
    {
        if (! class_exists($className)) {
            throw new InvalidArgumentException('The given indexer class: "' . $className . '" does not exist');
        }
        
        if (! in_array($this->requiredInterface, class_implements($className), true)) {
            throw new InvalidArgumentException(
                'The given indexer class: "' . $className .
                '" does not implement the required interface: "' . $this->requiredInterface . '"');
        }
        
        $this->classes[$className] = $options;
        
        return $this;
    }
    
    /**
     * The same as "add" but allows to provide an array of class names instead
     *
     * @param   array  $classNames  An array of class names to be added to the list
     *                              Either as list of class names or as ClassName => options map
     *
     * @return $this
     */
    public function addMultiple(array $classNames): self
    {
        foreach ($classNames as $a => $b) {
            if (is_array($b)) {
                $this->add((string)$a, $b);
            } elseif (is_string($b)) {
                $this->add($b);
            } else {
                throw new InvalidArgumentException(
                    'Invalid class list given. Only arrays of class names, or associative arrays of ' .
                    'className => option mappings are supported');
            }
        }
        
        return $this;
    }
    
    /**
     * Removes a previously registered indexer class from the list
     *
     * @param   string  $className  The name of the indexer class to remove
     *
     * @return $this
     */
    public function remove(string $className): self
    {
        unset($this->classes[$className]);
        
        return $this;
    }
    
    /**
     * Returns all registered indexer classes in this list
     *
     * @return array
     */
    public function getAll(): array
    {
        return $this->classes;
    }
    
    /**
     * Removes all previously registered indexers from the list
     *
     * @return $this
     */
    public function clear(): self
    {
        $this->classes = [];
        
        return $this;
    }
}