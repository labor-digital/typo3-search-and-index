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
 * Last modified: 2022.04.13 at 14:19
 */

declare(strict_types=1);


namespace LaborDigital\T3sai\Search\Indexer\Page\ContentElement;


use LaborDigital\T3ba\Core\Di\ContainerAwareTrait;
use LaborDigital\T3ba\Tool\Tca\ContentType\Domain\AbstractDataModel;
use LaborDigital\T3ba\Tool\Tca\ContentType\Domain\ContentRepository;

abstract class AbstractContentElementIndexer implements ContentElementIndexerInterface
{
    use ContainerAwareTrait;
    
    /**
     * Options, provided when the indexer was registered
     *
     * @var array|null
     */
    protected $options;
    
    /**
     * @inheritDoc
     */
    public function setOptions(?array $options): void
    {
        $this->options = $options;
    }
    
    /**
     * Returns the instance of the content repository, to read and write the tt_content records with.
     *
     * @return \LaborDigital\T3ba\Tool\Tca\ContentType\Domain\ContentRepository
     */
    protected function getContentRepository(): ContentRepository
    {
        return $this->makeInstance(ContentRepository::class);
    }
    
    /**
     * Returns a new instance of the data model for the tt_content record linked to this content element/plugin
     *
     * @return mixed
     */
    protected function getDataModel(array $row): AbstractDataModel
    {
        return $this->getContentRepository()->hydrateModel($row);
    }
}