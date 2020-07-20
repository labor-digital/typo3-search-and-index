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
 * Last modified: 2020.07.16 at 17:10
 */

declare(strict_types=1);
/**
 * Copyright 2019 LABOR.digital
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
 * Last modified: 2019.09.02 at 18:08
 */

namespace LaborDigital\T3SAI\Configuration;


class PageDataTransformerConfig
{
    /**
     * By default the indexer will start at the root pid of the domain's site,
     * but you can also add your own root pids to override that behaviour
     *
     * @var array
     */
    public $rootPids = [];
    
    /**
     * If this is set to true (default), the page data provider will be used.
     * If this is false the data provider is ignored.
     *
     * @var bool
     */
    public $active = true;
    
    /**
     * The db column where the title attribute should be read from
     *
     * @var string
     */
    public $titleCol = 'title';
    
    /**
     * The db column where the title attribute should be read from if the main title column contained an empty value
     *
     * @var string
     */
    public $titleFallbackCol = 'title';
    
    /**
     * The db column where the description should be read from
     *
     * @var string
     */
    public $descriptionCol = 'description';
    
    /**
     * A file reference column which points to the media data that should be used as preview image
     *
     * @var string
     */
    public $imageCol = 'media';
    
    /**
     * A list of additional columns to load from the "pages" database table and add to the searchable content
     *
     * @var array
     */
    public $searchableColumns
        = [
            'og_description',
            'og_title',
            'twitter_title',
            'twitter_description',
        ];
    
    /**
     * Contains the average root line length of all pages.
     * NOTE: If you set this manually, the default calculation after searchAndIndex__pageProvider--filterPages
     * will not be executed!
     *
     * @var int
     */
    public $averageRootLineLength = 0;
}
