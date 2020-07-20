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
 * Last modified: 2019.09.02 at 18:09
 */

namespace LaborDigital\T3SAI\Configuration;


use GeorgRinger\News\Controller\NewsController;
use GeorgRinger\News\Domain\Repository\NewsRepository;

class NewsDataTransformerConfig
{
    /**
     * If this is set to true, the news data provider will be used.
     * If this is false (default) the data provider is ignored.
     *
     * @var bool
     */
    public $active = false;
    
    /**
     * A pid of a page containing the news detail plugin that is
     * used if there was no explicit mapping in $storagePidToDetailPidMap
     *
     * @var int
     */
    public $detailPid = 0;
    
    /**
     * The list of storage pids to read the news from
     * This will be merged with the keys of $storagePidToDetailPidMap and $storagePidToTagMap
     *
     * @var array
     */
    public $storagePids = [];
    
    /**
     * A list of storage pids and the corresponding tag for the node
     *
     * @var array
     */
    public $storagePidToTagMap = [];
    
    /**
     * A list of storage pids and the corresponding pid of the detail page
     *
     * @var array
     */
    public $storagePidToDetailPidMap = [];
    
    /**
     * Holds a list of storage pid's and matching link sets.
     * The link sets should expect an argument with an "news" key.
     * If a storage pid is given in both $storagePidToLinkSetMap and $storagePidToDetailPidMap
     * the definition in $storagePidToLinkSetMap will win.
     *
     * @var array
     */
    public $storagePidToLinkSetMap = [];
    
    /**
     * A list of storage pids that should be ignored when the node priority is calculated
     * based on it's timestamp
     *
     * @var array
     */
    public $storagePidsToIgnoreInTimestampPriority = [];
    
    /**
     * A list of storage pids that will now show up on the sitemap.xml
     *
     * @var array
     */
    public $storagePidsToHideOnSiteMap = [];
    
    /**
     * The name of the repository class to use.
     *
     * @var string
     */
    public $repositoryClass = NewsRepository::class;
    
    /**
     * The plugin name that is used when the link for the element is created
     * Is used when a link for a news element is created that is not mapped using a link set.
     * Can be changed to match the requirements of your setup
     *
     * @var string
     */
    public $linkPluginName = 'pi1';
    
    /**
     * The controller class that is used when the link for the element is created
     * Is used when a link for a news element is created that is not mapped using a link set.
     * Can be changed to match the requirements of your setup
     *
     * @var string
     */
    public $linkControllerClass = NewsController::class;
    
    /**
     * The controller action name (without ...Action) that is used when the link for the element is created
     * Is used when a link for a news element is created that is not mapped using a link set.
     * Can be changed to match the requirements of your setup
     *
     * @var string
     */
    public $linkControllerAction = 'detail';
}
