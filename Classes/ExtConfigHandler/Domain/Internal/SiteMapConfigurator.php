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
 * Last modified: 2022.04.11 at 13:02
 */

declare(strict_types=1);


namespace LaborDigital\T3sai\ExtConfigHandler\Domain\Internal;


use LaborDigital\T3ba\Core\Di\NoDiInterface;

class SiteMapConfigurator implements NoDiInterface
{
    protected $enabled = true;
    protected $addChangeFreq = false;
    protected $addPriority = false;
    
    /**
     * Returns true if the sitemap is enabled for this domain, false if not
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }
    
    /**
     * True by default, can be set to false, to disable the site map for this domain.
     *
     * @param   bool  $enabled
     *
     * @return \LaborDigital\T3sai\ExtConfigHandler\Domain\Internal\SiteMapConfigurator
     */
    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;
        
        return $this;
    }
    
    /**
     * Returns true if the "changeFreq" node should be attached to side map nodes
     *
     * @return bool
     */
    public function getAddChangeFreq(): bool
    {
        return $this->addChangeFreq;
    }
    
    /**
     * False by default, if set to true, the changeFreq node will be added to the
     * site map nodes.
     *
     * @param   bool  $addChangeFreq
     *
     * @return \LaborDigital\T3sai\ExtConfigHandler\Domain\Internal\SiteMapConfigurator
     */
    public function setAddChangeFreq(bool $addChangeFreq): self
    {
        $this->addChangeFreq = $addChangeFreq;
        
        return $this;
    }
    
    /**
     * Returns true if the "priority" node should be attached to side map nodes
     *
     * @return bool
     */
    public function getAddPriority(): bool
    {
        return $this->addPriority;
    }
    
    /**
     * False by default, if set to true, the priority node will be added to the
     * site map nodes.
     *
     * @param   bool  $addPriority
     *
     * @return \LaborDigital\T3sai\ExtConfigHandler\Domain\Internal\SiteMapConfigurator
     */
    public function setAddPriority(bool $addPriority): self
    {
        $this->addPriority = $addPriority;
        
        return $this;
    }
    
    /**
     * Returns the configured state as array
     *
     * @return array
     */
    public function asArray(): array
    {
        return [
            'enabled' => $this->enabled,
            'addChangeFreq' => $this->addChangeFreq,
            'addPriority' => $this->addPriority,
        ];
    }
}