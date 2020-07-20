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
 * Last modified: 2019.09.03 at 07:59
 */

namespace LaborDigital\T3SAI\Controller\ContentElement;


use LaborDigital\Typo3BetterApi\ExtConfig\ExtConfigContext;
use LaborDigital\Typo3FrontendApi\ContentElement\Configuration\ContentElementConfigurator;
use LaborDigital\Typo3FrontendApi\ContentElement\Controller\AbstractContentElementController;
use LaborDigital\Typo3FrontendApi\ContentElement\Controller\ContentElementControllerContext;

class SearchContentElement extends AbstractContentElementController
{
    
    /**
     * @inheritDoc
     */
    public static function configureElement(ContentElementConfigurator $configurator, ExtConfigContext $context): void
    {
        $configurator
            ->setTitle('saibe.searchField')
            ->setDescription('saibe.searchFieldDesc')
            ->setWizardTab('plugins');
    }
    
    /**
     * @inheritDoc
     */
    public function handle(ContentElementControllerContext $context)
    {
        $context->setInitialStateQuery('search', [
            'query' => '',
        ]);
    }
    
    /**
     * @inheritDoc
     */
    public function handleBackend(ContentElementControllerContext $context): string
    {
        return $this->Translation()->translate('saibe.searchFieldDesc');
    }
    
}
