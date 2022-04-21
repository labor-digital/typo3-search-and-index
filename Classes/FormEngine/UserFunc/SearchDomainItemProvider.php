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
 * Last modified: 2022.04.21 at 11:38
 */

declare(strict_types=1);


namespace LaborDigital\T3sai\FormEngine\UserFunc;


use LaborDigital\T3ba\Core\Di\ContainerAwareTrait;
use LaborDigital\T3sai\Domain\Repository\DomainConfigRepository;
use Neunerlei\Inflection\Inflector;

class SearchDomainItemProvider
{
    use ContainerAwareTrait;
    
    public function provideDomains(array &$args): void
    {
        foreach ($this->makeInstance(DomainConfigRepository::class)->findDomainsForSite(null) as $domainIdentifier => $_) {
            $args['items'][] = [Inflector::toHuman($domainIdentifier), $domainIdentifier];
        }
    }
}