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

namespace LaborDigital\T3sai\Core\Indexer\Node;


use DateTime;
use LaborDigital\T3ba\Core\Di\NoDiInterface;
use LaborDigital\T3ba\Tool\Link\Link;
use LaborDigital\T3sai\Core\Indexer\Queue\QueueRequest;
use Neunerlei\TinyTimy\DateTimy;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

class Node implements NoDiInterface
{
    /**
     * A unique id for this index node
     *
     * @var string
     */
    protected $guid;
    
    /**
     * The request object that was used to generate the node
     *
     * @var QueueRequest
     */
    protected $request;
    
    /**
     * Used to generate a new link instance through the link service
     *
     * @var callable
     */
    protected $linkFactory;
    
    /**
     * The key of the domain we are currently working with
     *
     * @var string
     */
    protected $domainIdentifier;
    
    /**
     * The list of registered content's and their priorities
     *
     * @var array
     */
    protected $contents = [];
    
    /**
     * The list of registered keywords
     *
     * @var array
     */
    protected $keywords = [];
    
    /**
     * The priority of this node in a range from 0 - 100.
     * The default priority is 40
     *
     * @var float
     */
    protected $priority = 40;
    
    /**
     * A title for this node in the search results
     *
     * @var string
     */
    protected $title = '';
    
    /**
     * The tag which defines where this node should be displayed in the search results
     *
     * @var string
     */
    protected $tag = 'general';
    
    /**
     * A description for this node in the search results
     *
     * @var string
     */
    protected $description = '';
    
    /**
     * True if this result should show up in the search results.
     * If this is set to false, you can create search entries that are only visible in the sitemap
     *
     * @var bool
     */
    protected $addToSearchResults = true;
    
    /**
     * True if this result should end up in the sitemap
     *
     * @var bool
     */
    protected $addToSiteMap = true;
    
    /**
     * This should be the timestamp of the last modification.
     * The timestamp will be used as additional priority. Older -> Less priority. Newer -> Higher priority
     *
     * @var \DateTime|null
     */
    protected $timestamp;
    
    /**
     * The registered link object to use if no hard url was given
     *
     * @var \LaborDigital\T3ba\Tool\Link\Link
     */
    protected $link;
    
    /**
     * Can be set to define a hard (fully qualified url) link for this node.
     * If set the link, which is configured using link() will be ignored!
     *
     * @var string|null
     */
    protected $hardLink;
    
    /**
     * The preview image to be used for this node in the search results.
     * This can either be a file object, or an absolute url to the image you may want to use
     *
     * @var mixed
     */
    protected $image = '';
    
    /**
     * Any kind of additional data that will be stored alongside the node in the database.
     * It can be used to store information for the output of the search result.
     * The content of this variable has to be JSON-serializable!
     *
     * @var mixed
     */
    protected $metaData;
    
    public function __construct(string $guid, QueueRequest $request, callable $linkFactory)
    {
        $this->guid = $guid;
        $this->request = $request;
        $this->linkFactory = $linkFactory;
    }
    
    /**
     * Returns the unique id for this node
     *
     * @return string
     */
    public function getGuid(): string
    {
        return $this->guid;
    }
    
    /**
     * Returns the language used for this node
     *
     * @return \TYPO3\CMS\Core\Site\Entity\SiteLanguage
     */
    public function getLanguage(): SiteLanguage
    {
        return $this->request->getLanguage();
    }
    
    /**
     * Returns the site for which the search domain is registered
     *
     * @return \TYPO3\CMS\Core\Site\Entity\Site
     */
    public function getSite(): Site
    {
        return $this->request->getSite();
    }
    
    /**
     * Returns the unique identifier of the domain this node is part of
     *
     * @return string
     */
    public function getDomainIdentifier(): string
    {
        return $this->request->getDomainIdentifier();
    }
    
    /**
     * Returns the configuration array for the search domain this node is part of
     *
     * @return array
     */
    public function getDomainConfig(): array
    {
        return $this->request->getDomainConfig();
    }
    
    /**
     * Returns the request instance which created this node
     *
     * @return \LaborDigital\T3sai\Core\Indexer\Queue\QueueRequest
     */
    public function getRequest(): QueueRequest
    {
        return $this->request;
    }
    
    /**
     * Completely reset's the list of internally stored contents
     * expects an iterable list where every child has two elements, the first is the content the second it's priority.
     *
     * In a normal use case you want to use addContent() over this method.
     * This is only useful if you want to filter the contents after adding them
     *
     * @param   iterable  $content
     *
     * @return $this
     */
    public function setContents(iterable $content): self
    {
        $this->contents = [];
        $this->keywords = [];
        foreach ($content as $c) {
            if (is_array($content)) {
                $this->addContent($c[0], $c[1]);
            } else {
                $this->addContent($c);
            }
        }
        
        return $this;
    }
    
    /**
     * Returns the list of all registered contents on this node.
     * Returns a list of elements that contain two children each.
     * The first is the readable content, the second its priority.
     *
     * @return iterable
     */
    public function getContents(): iterable
    {
        return $this->contents;
    }
    
    /**
     * Adds a new content to the list of contents.
     *
     * @param   string|null  $content   The content to add
     * @param   int          $priority  A priority to set for this content
     *
     * @return $this
     */
    public function addContent(?string $content, int $priority = 0): self
    {
        if (is_string($content)) {
            $this->contents[] = [$content, $priority];
        }
        
        return $this;
    }
    
    /**
     * Returns the list of all registered keywords and their defined priorities
     *
     * @return array
     */
    public function getKeywords(): iterable
    {
        return $this->keywords;
    }
    
    /**
     * Resets the currently set keywords to the list of keywords given
     *
     * @param   array  $keywords
     *
     * @return $this
     */
    public function setKeywords(iterable $keywords): self
    {
        $this->keywords = [];
        foreach ($keywords as $keyword) {
            if (is_array($keyword)) {
                $this->addKeywords($keyword[0], $keyword[1]);
            } else {
                $this->addKeywords($keyword);
            }
        }
        
        return $this;
    }
    
    /**
     * Adds a new keyword or a list of keywords to the node.
     * Keywords can be searched but will not be visible for the frontend user!
     *
     * @param   array|string  $keywords
     * @param   int           $priority
     *
     * @return $this
     */
    public function addKeywords($keywords, int $priority = 50): self
    {
        $this->keywords[] = [$keywords, $priority];
        
        return $this;
    }
    
    /**
     * Returns the currently set priority for this node
     *
     * @return float
     */
    public function getPriority(): float
    {
        return $this->priority;
    }
    
    /**
     * Sets the priority of this node
     * Range: (less important) 0 - 100 (really important)
     *
     * @param   float  $priority
     * @param   bool   $ignoreGuardRails  If set to true, we don't validate your priority, so stuff like 200, 300... is
     *                                    possible
     *
     * @return Node
     */
    public function setPriority(float $priority, bool $ignoreGuardRails = false): self
    {
        $this->priority = $ignoreGuardRails ? $priority : max(0, min(100, $priority));
        
        return $this;
    }
    
    /**
     * Returns the title that was set for this node
     *
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }
    
    /**
     * Sets the title of this node that will be visible in the search results
     *
     * @param   string  $title
     *
     * @return Node
     */
    public function setTitle(string $title): self
    {
        $this->title = $title;
        
        return $this;
    }
    
    /**
     * Returns the current tag / facet of this node
     *
     * @return string
     */
    public function getTag(): string
    {
        return $this->tag;
    }
    
    /**
     * Sets the tag / the "facet" of this node, which describes what kind of entry this is.
     * Can be any text you want, probably something you want to translate later
     *
     * @param   string  $tag
     *
     * @return Node
     */
    public function setTag(string $tag): self
    {
        $this->tag = $tag;
        
        return $this;
    }
    
    /**
     * Returns the currently set, visible description of this node
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }
    
    /**
     * Can be used to set a description that will be shown in the search results
     *
     * @param   string  $description
     *
     * @return Node
     */
    public function setDescription(string $description): self
    {
        $this->description = $description;
        
        return $this;
    }
    
    /**
     * Returns true, if this page should show up in the site map, false if not
     *
     * @return bool
     * @deprecated use addToSiteMap() instead
     */
    public function isAddToSiteMap(): bool
    {
        return $this->addToSiteMap;
    }
    
    /**
     * Returns true, if this page should show up in the site map, false if not
     *
     * @return bool
     */
    public function addToSiteMap(): bool
    {
        return $this->addToSiteMap;
    }
    
    /**
     * Can be set to true to add the element to the generated sitemap.xml
     *
     * @param   bool  $addToSiteMap
     *
     * @return Node
     */
    public function setAddToSiteMap(bool $addToSiteMap): self
    {
        $this->addToSiteMap = $addToSiteMap;
        
        return $this;
    }
    
    /**
     * True if this result should show up in the search results.
     * If this is set to false, you can create search entries that are only visible in the sitemap
     *
     * @param   bool  $addToSearchResults
     *
     * @return Node
     */
    public function setAddToSearchResults(bool $addToSearchResults): self
    {
        $this->addToSearchResults = $addToSearchResults;
        
        return $this;
    }
    
    /**
     * Returns true if this result should show up in the search results. False if the search should not find this node.
     *
     * @return bool
     */
    public function addToSearchResults(): bool
    {
        return $this->addToSearchResults;
    }
    
    /**
     * Returns the currently set timestamp of this node
     *
     * @return \DateTime
     */
    public function getTimestamp(): DateTime
    {
        return $this->timestamp ?? new DateTimy();
    }
    
    /**
     * Can be used to set the timestamp for this node.
     * This can be either a future date (events) or a past event (news, blog, ...)
     * This value is used to determine the importance of this node
     *
     * @param   \DateTime|null  $timestamp
     *
     * @return Node
     */
    public function setTimestamp(?DateTime $timestamp): self
    {
        $this->timestamp = $timestamp;
        
        return $this;
    }
    
    /**
     * Returns the link builder which is used to define the link to this element
     *
     * @param   string|null    $linkSet       Defines the link set which was previously defined in typoscript,
     *                                        or using the LinkSetRepository in your php code. The set will
     *                                        automatically be applied to the new link instance
     * @param   iterable|null  $args          If you have a linkSet specified you can use this parameter to supply
     *                                        additional arguments to the created link instance directly
     * @param   iterable|null  $fragmentArgs  If you have a linkSet specified you can use this parameter to supply
     *                                        arguments to your fragment of the created link instance directly
     *
     * @return $this
     */
    public function setLink(?string $linkSet = null, ?iterable $args = null, ?iterable $fragmentArgs = null): self
    {
        $this->link = ($this->linkFactory)($linkSet, $args, $fragmentArgs);
        
        return $this;
    }
    
    /**
     * Allows you to pass a link builder which defines the link for this element
     *
     * @param   \LaborDigital\T3ba\Tool\Link\Link  $link
     *
     * @return $this
     */
    public function setLinkInstance(Link $link): self
    {
        $this->link = $link;
        
        return $this;
    }
    
    /**
     * As the link object is immutable you may use this method by supplying a closure which can be used
     * to modify the link instance. The callback MUST return a new typo link instance that will be used for this node.
     *
     * @param   callable  $editor
     *
     * @return $this
     * @throws \LaborDigital\T3sai\Exception\SearchAndIndexException
     */
    public function editLink(callable $editor): self
    {
        if ($this->link === null) {
            $this->setLink();
            $this->link = $this->link->withLanguage($this->getLanguage());
        }
        
        $this->link = $editor($this->link);
        
        return $this;
    }
    
    /**
     * Returns the link instance of this node.
     * Note: The link instance is immutable! Use {@link editLink to edit the link}
     *
     * @return \LaborDigital\T3ba\Tool\Link\Link|null
     */
    public function getLink(): ?Link
    {
        return $this->link;
    }
    
    /**
     * Returns the hard (fully qualified url) link of this node.
     *
     * @return string|null
     */
    public function getHardLink(): ?string
    {
        return $this->hardLink;
    }
    
    /**
     * Can be set to define a hard (fully qualified url) link for this node.
     * If set the link, which is configured using link() will be ignored!
     * It is highly recommended to use link() instead of this method!
     *
     * @param   string|null  $hardLink
     *
     * @return Node
     */
    public function setHardLink(?string $hardLink): self
    {
        $this->hardLink = $hardLink;
        
        return $this;
    }
    
    /**
     * Returns either an image object or a fully qualified url as a string, representing the preview image of this node
     *
     * @return mixed
     */
    public function getImage()
    {
        return $this->image;
    }
    
    /**
     * Sets the preview image to be used for this node in the search results.
     * This can either be a file object, or an absolute url to the image you may want to use
     *
     * @param   mixed  $image
     *
     * @return Node
     */
    public function setImage($image): self
    {
        $this->image = $image;
        
        return $this;
    }
    
    /**
     * Can be used to add any kind of additional data that will be stored alongside the node in the database.
     * It can be used to store information for the output of the search result.
     *
     * @param   mixed  $data  The data to be attached to this node. It has to be JSON-serializable!
     *
     * @return $this
     */
    public function setMetaData($data): self
    {
        $this->metaData = $data;
        
        return $this;
    }
    
    /**
     * Returns the metadata that should be stored alongside the node data for retrieval when the result is found
     *
     * @return mixed
     */
    public function getMetaData()
    {
        return $this->metaData;
    }
}
