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
 * Last modified: 2020.07.16 at 18:48
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
 * Last modified: 2019.09.02 at 17:36
 */

namespace LaborDigital\T3SAI\Builtin\Transformer\Page\ContentElement;


use LaborDigital\T3SAI\Indexer\IndexerContext;

class DefaultContentElementTransformer implements ContentElementTransformerInterface
{
    
    /**
     * @inheritDoc
     */
    public function canHandle(string $cType, string $listType, array $row, IndexerContext $context): bool
    {
        return true;
    }
    
    /**
     * @inheritDoc
     */
    public function convert(array $row, IndexerContext $context): string
    {
        if ($row["CType"] === "table") {
            $row["bodytext"] = str_replace(["\t", ",", ";", "|"], " ", $row["bodytext"]);
        }
        
        return $row["header"] . " " . $row["subheader"] . " " . $row["bodytext"];
    }
}
