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
 * Last modified: 2022.04.25 at 12:02
 */

declare(strict_types=1);


namespace LaborDigital\T3sai\Core\Indexer\Node\Converter;


use LaborDigital\T3ba\Tool\Fal\FalException;
use LaborDigital\T3ba\Tool\Fal\FalService;

class ImageConverter
{
    
    /**
     * @var \LaborDigital\T3ba\Tool\Fal\FalService
     */
    protected $falService;
    
    public function __construct(FalService $falService)
    {
        $this->falService = $falService;
    }
    
    
    /**
     * Tries to extract a provided node image or iterable of images into a single image url
     *
     * @param $image
     *
     * @return string
     */
    public function convertImageToLink($image): string
    {
        if (is_iterable($image)) {
            $image = $this->extractImageFromIterable($image);
        }
        
        if (empty($image)) {
            return '';
        }
        
        /** @noinspection BypassedUrlValidationInspection */
        if (is_string($image) && filter_var($image, FILTER_VALIDATE_URL)) {
            return $image;
        }
        
        return $this->falService->getFileUrl($image);
    }
    
    /**
     * Tries to extract the image file source (if it is a FAL file) and returns it.
     * The information can later be used to restore the image information for frontend processing.
     * The source is stored either as ref:$uid for a file reference, or as file:$uid for a FAL file.
     * An empty string is returned if a source could not be found
     *
     * @param $image
     *
     * @return string
     */
    public function convertImageToSource($image): string
    {
        if (is_iterable($image)) {
            $image = $this->extractImageFromIterable($image);
        }
        
        if (empty($image)) {
            return '';
        }
        
        try {
            $info = $this->falService->getFileInfo($image);
        } catch (FalException $e) {
            return '';
        }
        
        if ($info->isFileReference()) {
            return 'ref:' . $info->getFileReferenceUid();
        }
        
        return 'file:' . $info->getFileUid();
    }
    
    /**
     * Extracts the first item in an iterable and returns it
     *
     * @param   iterable  $images
     *
     * @return mixed|null
     */
    protected function extractImageFromIterable(iterable $images)
    {
        foreach ($images as $i) {
            return $i;
        }
        
        return null;
    }
    
    
}