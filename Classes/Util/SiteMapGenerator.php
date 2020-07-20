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
 * Last modified: 2020.07.17 at 12:34
 */

declare(strict_types=1);

namespace LaborDigital\T3SAI\Util;

use LaborDigital\Typo3BetterApi\Link\LinkService;
use Neunerlei\Options\Options;
use Neunerlei\TinyTimy\DateTimy;
use TYPO3\CMS\Core\SingletonInterface;

class SiteMapGenerator implements SingletonInterface
{
    /**
     * @var \LaborDigital\Typo3BetterApi\Link\LinkService
     */
    protected $links;
    
    /**
     * SiteMapGenerator constructor.
     *
     * @param   \LaborDigital\Typo3BetterApi\Link\LinkService  $links
     */
    public function __construct(LinkService $links)
    {
        $this->links = $links;
    }
    
    /**
     * Expects to receive the result of SearchRepository::findSitemap()
     * Builds a valid xml sitemap as string and returns it
     *
     * @param   array  $structure  The structure array to be rendered as sitemap
     * @param   array  $options    Additional configuration options for the site map generation
     *                             - addChangeFreq bool (FALSE): if set to true, the changeFreq node will be added to the
     *                             site map elements.
     *                             - addPriority bool (FALSE): if set to true, the priority node will be added to the
     *                             site map elements.
     *
     * @return string
     */
    public function build(array $structure, array $options = []): string
    {
        // Prepare options
        $options = Options::make($options, [
            'addChangeFreq' => false,
            'addPriority'   => false,
        ]);
        
        // Check if we have images
        $hasImages = false;
        foreach ($structure as $row) {
            if (empty($row['image'])) {
                continue;
            }
            $hasImages = true;
            break;
        }
        
        // Create the xml document
        $xmlHeader = $hasImages ? ' xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"' : '';
        $xmlHeader = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' .
                     $xmlHeader . '></urlset>';
        $xml       = simplexml_load_string($xmlHeader);
        
        // Prepare url
        $baseUrl = $this->links->getHost();
        
        // Loop through the structure
        foreach ($structure as $row) {
            // Calculate priority
            $priority = round(($row['priority'] + 100) / 200, 1);
            
            // Merge the urls
            $url = ltrim($row['url'], '/');
            if (stripos($url, $baseUrl) !== 0) {
                $url = $baseUrl . $url;
            }
            
            // Calculate change frequency
            $frequency = 'daily';
            if ($priority <= 0) {
                $frequency = 'monthly';
            } elseif ($priority < 0.3) {
                $frequency = 'weekly';
            }
            
            // Build date
            $date = (new DateTimy($row['timestamp']))->format('Y-m-d\TH:i:sP');
            
            // Create new node
            $node = $xml->addChild('url');
            $node->addChild('loc', htmlspecialchars($url));
            $node->addChild('lastmod', $date);
            if ($options['addChangeFreq']) {
                $node->addChild('changefreq', $frequency);
            }
            if ($options['addPriority']) {
                $node->addChild('priority', $priority);
            }
            
            // Add image if required
            if (! empty($row['image'])) {
                $n = $node->addChild('image:image');
                $n->addChild('image:loc', $row['image']);
            }
        }
        
        // Done
        return $xml->saveXML();
    }
}
