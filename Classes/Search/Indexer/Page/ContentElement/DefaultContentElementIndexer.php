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
 * Last modified: 2022.04.12 at 20:48
 */

declare(strict_types=1);


namespace LaborDigital\T3sai\Search\Indexer\Page\ContentElement;


use LaborDigital\T3sai\Core\Indexer\Node\Node;

class DefaultContentElementIndexer implements ContentElementIndexerInterface
{
    /**
     * @inheritDoc
     */
    public function setOptions(?array $options): void { }
    
    /**
     * @inheritDoc
     */
    public function canHandle(string $cType, string $listType, array $row, Node $node): bool
    {
        return true;
    }
    
    /**
     * @inheritDoc
     */
    public function generateContent(array $row, Node $node): string
    {
        if ($row['CType'] === 'table') {
            $row['bodytext'] = str_replace(["\t", ',', ';', '|'], ' ', $row['bodytext']);
        }
        
        return $row['header'] . ' ' . $row['subheader'] . ' ' . $row['bodytext'];
    }
}