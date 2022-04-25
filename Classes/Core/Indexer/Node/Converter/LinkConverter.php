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
 * Last modified: 2022.04.12 at 11:15
 */

declare(strict_types=1);


namespace LaborDigital\T3sai\Core\Indexer\Node\Converter;


use LaborDigital\T3ba\Tool\Link\Link;
use LaborDigital\T3ba\Tool\Link\LinkService;
use LaborDigital\T3sai\Exception\Indexer\IndexerException;

class LinkConverter
{
    /**
     * @var \LaborDigital\T3ba\Tool\Link\LinkService
     */
    protected $linkService;
    
    public function __construct(LinkService $linkService)
    {
        $this->linkService = $linkService;
    }
    
    /**
     * Tries to extract either the hard link or build the link instance into a string
     *
     * @param   string|null                             $hardLink
     * @param   \LaborDigital\T3ba\Tool\Link\Link|null  $link
     *
     * @return string
     * @throws \LaborDigital\T3sai\Exception\Indexer\IndexerException
     */
    public function convertLink(?string $hardLink, ?Link $link): string
    {
        if (! empty($hardLink)) {
            return $hardLink;
        }
        
        if (! $link) {
            throw new IndexerException('Could not extract link from node, because there is neither a link, nor a hardLink set');
        }
        
        $linkString = (string)$link;
        
        if (empty($linkString)) {
            throw new IndexerException('Node link returned an empty string');
        }
        
        return $linkString;
    }
}