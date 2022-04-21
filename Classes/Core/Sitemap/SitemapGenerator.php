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
 * Last modified: 2022.04.20 at 11:26
 */

declare(strict_types=1);


namespace LaborDigital\T3sai\Core\Sitemap;


use DateTime;
use LaborDigital\T3sai\Core\Indexer\Node\Node;
use LaborDigital\T3sai\Domain\Repository\SitemapRepository;
use LaborDigital\T3sai\Event\SitemapBeforeGenerationFilterEvent;
use Neunerlei\TinyTimy\DateTimy;
use Psr\EventDispatcher\EventDispatcherInterface;
use Thepixeldeveloper\Sitemap\ChunkedUrlset;
use Thepixeldeveloper\Sitemap\Drivers\XmlWriterDriver;
use Thepixeldeveloper\Sitemap\Extensions\Image;
use Thepixeldeveloper\Sitemap\Interfaces\DriverInterface;
use Thepixeldeveloper\Sitemap\Sitemap;
use Thepixeldeveloper\Sitemap\SitemapIndex;
use Thepixeldeveloper\Sitemap\Url;

class SitemapGenerator
{
    public static $cssPath = '/typo3conf/ext/t3sai/Resources/Public/Css/Sitemap.xsl';
    
    /**
     * @var ChunkedUrlset[][]
     */
    protected $lists = [];
    
    /**
     * @var array
     */
    protected $knownUrls = [];
    
    /**
     * @var \LaborDigital\T3sai\Domain\Repository\SitemapRepository
     */
    protected $repository;
    
    /**
     * @var \Psr\EventDispatcher\EventDispatcherInterface
     */
    protected $eventDispatcher;
    
    public function __construct(SitemapRepository $repository, EventDispatcherInterface $eventDispatcher)
    {
        $this->repository = $repository;
        $this->eventDispatcher = $eventDispatcher;
    }
    
    /**
     * Adds a new node to be persisted in the sitemap
     *
     * @param   \LaborDigital\T3sai\Core\Indexer\Node\Node  $node
     * @param   array                                       $nodeRow
     *
     * @return void
     */
    public function addNode(Node $node, array $nodeRow): void
    {
        if (! $node->addToSiteMap()) {
            return;
        }
        
        $config = $node->getDomainConfig();
        if (($config['siteMap']['enabled'] ?? null) === false) {
            return;
        }
        
        // If there are results in multiple domains we might get them provided multiple times
        // we only want them once, tho. Therefore, we need to keep track of the urls and ignore multiple nodes
        $urlHash = md5($nodeRow['url']);
        if (isset($this->knownUrls[$urlHash])) {
            return;
        }
        $this->knownUrls[$urlHash] = true;
        
        $url = new Url($nodeRow['url']);
        $this->findListForNode($node)->add($url);
        
        $url->setLastMod($node->getTimestamp());
        
        if (! empty($nodeRow['image'])) {
            $image = new Image($nodeRow['image']);
            if (! empty($nodeRow['title'])) {
                $image->setCaption($nodeRow['title']);
            }
            $url->addExtension($image);
        }
        
        $priority = min(1, round(($nodeRow['priority'] + 100) / 200, 1));
        if (($config['siteMap']['addPriority'] ?? null) === true) {
            $url->setPriority((string)$priority);
        }
        
        if (($config['siteMap']['addChangeFreq'] ?? null) === true) {
            $frequency = 'daily';
            if ($priority <= 0) {
                $frequency = 'monthly';
            } elseif ($priority < 0.3) {
                $frequency = 'weekly';
            }
            $url->setChangeFreq($frequency);
        }
    }
    
    /**
     * Generates get list of xml files for all items in the sitemap and stores them on the harddrive
     *
     * @return void
     */
    public function generate(): void
    {
        $this->repository->flushAll();
        
        $e = $this->eventDispatcher->dispatch(new SitemapBeforeGenerationFilterEvent($this->lists));
        
        foreach ($e->getLists() as $siteHost => $fileList) {
            $indexList = new SitemapIndex();
            
            foreach ($fileList as $fileKey => $list) {
                foreach ($list->getCollections() as $c => $collection) {
                    $driver = $this->makeDriver();
                    $collection->accept($driver);
                    $filename = '/' . $siteHost . '/' . $fileKey . '_' . $c . '.xml';
                    $this->repository->persistSitemap($filename, $driver->output());
                    
                    // We use this marker to replace the location dynamically when it is rendered
                    $sitemap = new Sitemap('REL((' . $filename . '))');
                    $sitemap->setLastMod($this->findLastModOfCollection($collection->all()));
                    $indexList->add($sitemap);
                }
            }
            
            $driver = $this->makeDriver();
            $indexList->accept($driver);
            $this->repository->persistSitemap('/' . $siteHost . '/sitemap.xml', $driver->output());
        }
        
        $this->reset();
    }
    
    /**
     * Resets the internal lists back to a blank slate
     *
     * @return void
     */
    public function reset(): void
    {
        $this->lists = [];
        $this->knownUrls = [];
    }
    
    /**
     * Resolves the correct url list for the environment the node is part of
     *
     * @param   \LaborDigital\T3sai\Core\Indexer\Node\Node  $node
     *
     * @return \Thepixeldeveloper\Sitemap\ChunkedUrlset
     */
    protected function findListForNode(Node $node): ChunkedUrlset
    {
        $baseUrl = $node->getSite()->getBase();
        $siteHost = $baseUrl->getHost();
        $fileKey = md5(
                       $node->getSite()->getIdentifier() . '_' .
                       $node->getLanguage()->getLanguageId()
                   ) . '_' .
                   $node->getLanguage()->getTwoLetterIsoCode();
        
        return $this->lists[$siteHost][$fileKey] ??
               ($this->lists[$siteHost][$fileKey] = new ChunkedUrlset());
    }
    
    /**
     * Iterates the list of all items in a collection and tires to extract the latest lastMod timestamp
     *
     * @param   array  $items
     *
     * @return \DateTime
     */
    protected function findLastModOfCollection(array $items): DateTime
    {
        $lastMod = null;
        
        foreach ($items as $item) {
            if (! is_callable([$item, 'getLastMod'])) {
                continue;
            }
            
            $itemLastMod = $item->getLastMod();
            if (! $itemLastMod instanceof DateTime) {
                continue;
            }
            
            if (! $lastMod || $lastMod < $itemLastMod) {
                $lastMod = $itemLastMod;
            }
        }
        
        return $lastMod ?? new DateTime();
    }
    
    /**
     * Creates a new driver instance to generate the xml output with
     *
     * @return DriverInterface
     */
    protected function makeDriver(): DriverInterface
    {
        $driver = new XmlWriterDriver();
        $driver->addComment('Sitemap generated on: ' . (new DateTimy())->formatDateAndTime());
        $driver->addProcessingInstructions('xml-stylesheet', 'type="text/xsl" href="' . static::$cssPath . '"');
        
        return $driver;
    }
}