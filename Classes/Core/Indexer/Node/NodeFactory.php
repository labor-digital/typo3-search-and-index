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
 * Last modified: 2022.04.11 at 14:22
 */

declare(strict_types=1);


namespace LaborDigital\T3sai\Core\Indexer\Node;


use LaborDigital\T3ba\Core\Di\ContainerAwareTrait;
use LaborDigital\T3ba\Tool\Link\LinkService;
use LaborDigital\T3sai\Core\Indexer\Queue\QueueRequest;

class NodeFactory
{
    use ContainerAwareTrait;
    
    /**
     * @var \LaborDigital\T3ba\Tool\Link\LinkService
     */
    protected $linkService;
    
    public function __construct(LinkService $linkService)
    {
        $this->linkService = $linkService;
    }
    
    /**
     * Generates a new node instance based on the given request object
     *
     * @param   \LaborDigital\T3sai\Core\Indexer\Queue\QueueRequest  $request
     *
     * @return \LaborDigital\T3sai\Core\Indexer\Node\Node
     */
    public function makeNode(QueueRequest $request): Node
    {
        return $this->makeInstance(
            Node::class,
            [
                $this->makeUuid(),
                $request,
                function () {
                    return $this->linkService->getLink(...func_get_args());
                },
            ]
        );
    }
    
    /**
     * Generates a random v4 UUID
     *
     * @return string
     */
    protected function makeUuid(): string
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            random_int(0, 0xffff), random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0x0fff) | 0x4000,
            random_int(0, 0x3fff) | 0x8000,
            random_int(0, 0xffff), random_int(0, 0xffff), random_int(0, 0xffff)
        );
    }
}