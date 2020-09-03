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
 * Last modified: 2020.07.16 at 14:59
 */

$EM_CONF[$_EXTKEY] = [
    "title"            => "LABOR - Typo3 - Search and Index",
    "description"      => "Labor search engine for typo3 records which also creates an xml sitemap if you desire",
    "author"           => "Martin Neundorfer",
    "author_email"     => "m.neundorfer@labor.digital",
    "category"         => "search",
    "author_company"   => "Labor.digital",
    "shy"              => "",
    "conflicts"        => "",
    "priority"         => "",
    "module"           => "",
    "state"            => "alpha",
    "internal"         => "",
    "uploadfolder"     => 0,
    "createDirs"       => "",
    "modify_tables"    => "",
    "clearCacheOnLoad" => 1,
    "lockType"         => "",
    "version"          => "9.2.0",
    "constraints"      => [
        "depends"   => [
            "typo3" => "9.0.0-9.99.99",
        ],
        "conflicts" => [
        ],
        "suggests"  => [
        ],
    ],
    "suggests"         => [
    
    ],
];
