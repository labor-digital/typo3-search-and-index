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
 * Last modified: 2022.04.20 at 17:04
 */

declare(strict_types=1);


namespace LaborDigital\T3sai\Core\Lookup\Backend\Processor;


use LaborDigital\T3sai\Core\Lookup\Request\LookupRequest;
use Neunerlei\Arrays\Arrays;

class SearchCountProcessor implements LookupResultProcessorInterface
{
    use ProcessorUtilTrait;
    
    /**
     * @inheritDoc
     */
    public function canHandle(LookupRequest $request): bool
    {
        return $request->getType() === LookupRequest::TYPE_SEARCH_COUNT;
    }
    
    /**
     * @inheritDoc
     */
    public function process(iterable $rows, LookupRequest $request): array
    {
        $result = Arrays::getList($this->iterableToArray($rows), ['count'], 'tag');
        $result['_total'] = is_array($result) ? array_sum(array_values($result)) : 0;
        
        return $result;
    }
    
}