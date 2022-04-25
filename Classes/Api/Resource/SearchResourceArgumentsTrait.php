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
 * Last modified: 2022.04.25 at 13:09
 */

declare(strict_types=1);


namespace LaborDigital\T3sai\Api\Resource;


use LaborDigital\T3fa\Core\Resource\Query\ResourceQuery;
use League\Route\Http\Exception\BadRequestException;
use Neunerlei\Arrays\Arrays;

trait SearchResourceArgumentsTrait
{
    /**
     * Prepares both the input, and common options for multiple search resources
     *
     * @param   \LaborDigital\T3fa\Core\Resource\Query\ResourceQuery  $resourceQuery
     *
     * @return array
     * @throws \League\Route\Http\Exception\BadRequestException
     */
    protected function prepareCommonArguments(ResourceQuery $resourceQuery): array
    {
        $args = $resourceQuery->getAdditional();
        
        // We look for "query" for legacy reasons, "input" is the new gold standard
        $input = $args['input'] ?? $args['query'] ?? null;
        if (! $input) {
            throw new BadRequestException('The "input" parameter is missing!');
        }
        
        $input = urldecode((string)$input);
        
        // Use the query parameters to configure the repository
        $options = [];
        if (! empty($args['L'])) {
            $options['language'] = (int)$args['L'];
        }
        if (! empty($args['tags'])) {
            $options['tags'] = Arrays::makeFromStringList($args['tags']);
        }
        if (! empty($args['domain'])) {
            $options['domain'] = $args['domain'];
        }
        
        return [$input, $options];
    }
}