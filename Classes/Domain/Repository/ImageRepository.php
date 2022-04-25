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
 * Last modified: 2022.04.25 at 12:42
 */

declare(strict_types=1);


namespace LaborDigital\T3sai\Domain\Repository;


use LaborDigital\T3ba\Tool\Fal\FalService;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Throwable;
use TYPO3\CMS\Core\SingletonInterface;

class ImageRepository implements SingletonInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;
    
    /**
     * @var \LaborDigital\T3ba\Tool\Fal\FalService
     */
    protected $falService;
    
    public function __construct(FalService $falService)
    {
        $this->falService = $falService;
    }
    
    /**
     * Helper to resolve a file/image object based on the "source" identifier.
     * Useful for restoring image objects based on the "image_source" column in the nodes table.
     * The source is either ref:$uid for a file reference, or file:$uid for a FAL file.
     * An empty string will always return a NULL value. All exceptions thrown while resolving
     * the file object will automatically result in a NULL value as wall.
     *
     * @param   string  $source
     *
     * @return \TYPO3\CMS\Core\Resource\FileReference|\TYPO3\CMS\Core\Resource\File|null
     */
    public function findBySource(string $source): ?object
    {
        if (empty($source)) {
            return null;
        }
        
        try {
            if (str_starts_with($source, 'file:')) {
                return $this->falService->getFile($source);
            }
            
            if (str_starts_with($source, 'ref:')) {
                return $this->falService->getFileReference((int)(substr($source, 4)));
            }
        } catch (Throwable $e) {
            if ($this->logger) {
                $this->logger->error('Failed to resolve file by source: "' . $source . '"', ['exception' => $e]);
            }
            
            return null;
        }
        
        return null;
    }
    
}