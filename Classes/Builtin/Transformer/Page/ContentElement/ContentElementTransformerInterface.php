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
 * Last modified: 2019.09.02 at 09:44
 */

namespace LaborDigital\T3SAI\Builtin\Transformer\Page\ContentElement;


use LaborDigital\T3SAI\Indexer\IndexerContext;

interface ContentElementTransformerInterface
{
    
    /**
     * Receives the cType, the listType and the database row of the tt_content table
     *
     * @param   string          $cType     The content element type that is set for this row
     * @param   string          $listType  If the cType is "list" the list_type column defines the extBase plugin
     * @param   array           $row       The raw database row that should be converted
     * @param   IndexerContext  $context   The index generator context to handle this row with
     *
     * @return bool
     */
    public function canHandle(string $cType, string $listType, array $row, IndexerContext $context): bool;
    
    /**
     * Receives the row and should return the string version of it
     *
     * @param   array           $row
     * @param   IndexerContext  $context
     *
     * @return string
     */
    public function convert(array $row, IndexerContext $context): string;
}
