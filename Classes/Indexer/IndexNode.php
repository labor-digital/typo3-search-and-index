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

namespace LaborDigital\T3SAI\Indexer;


use DateTime;
use LaborDigital\T3SAI\Domain\Repository\SearchRepository;
use LaborDigital\T3SAI\Event\IndexNodeDbDataFilterEvent;
use LaborDigital\T3SAI\Exception\Indexer\IndexNodeException;
use LaborDigital\Typo3BetterApi\Link\TypoLink;
use Neunerlei\Inflection\Inflector;
use Neunerlei\TinyTimy\DateTimy;

class IndexNode
{
    /**
     * Internal property to generate unique id's for each node
     *
     * @var int
     */
    protected static $guidCounter = 0;
    
    /**
     * A unique id for this index node
     *
     * @var string
     */
    protected $guid;
    
    /**
     * The indexer context used to create this node
     *
     * @var \LaborDigital\T3SAI\Indexer\IndexerContext
     */
    protected $context;
    
    /**
     * The language of this node
     *
     * @var \LaborDigital\T3SAI\Indexer\IndexerSiteLanguage
     */
    protected $language;
    
    /**
     * The key of the domain we are currently working with
     *
     * @var string
     */
    protected $domainKey;
    
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
     * @var \LaborDigital\Typo3BetterApi\Link\TypoLink
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
    
    /**
     * IndexNode constructor.
     *
     * @param   \LaborDigital\T3SAI\Indexer\IndexerContext  $context
     */
    public function __construct(IndexerContext $context)
    {
        $this->guid      = Inflector::toUuid(microtime(true) . ' ' . ++static::$guidCounter);
        $this->domainKey = $context->getDomainKey();
        $this->context   = $context;
        $this->language  = $context->getLanguage();
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
     * Completely reset's the list of internally stored contents
     * expects an iterable list where every child has two elements, the first is the content the second it's priority.
     *
     * In a normal use case you want to use addContent() over this method.
     * This is only useful if you want to filter the contents after adding them
     *
     * @param   iterable  $content
     *
     * @return \LaborDigital\T3SAI\Indexer\IndexNode
     */
    public function setContents(iterable $content): IndexNode
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
     * Returns a list of elements that contain two children each. The first is the readable content, the second it's
     * priority.
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
     * @param   string  $content   The content to add
     * @param   int     $priority  A priority to set for this content
     *
     * @return \LaborDigital\T3SAI\Indexer\IndexNode
     */
    public function addContent(string $content, int $priority = 0): IndexNode
    {
        $this->contents[] = [$content, $priority];
        
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
     * @return \LaborDigital\T3SAI\Indexer\IndexNode
     */
    public function setKeywords(iterable $keywords): IndexNode
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
     * @return \LaborDigital\T3SAI\Indexer\IndexNode
     */
    public function addKeywords($keywords, int $priority = 50): IndexNode
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
     * @return IndexNode
     */
    public function setPriority(float $priority, bool $ignoreGuardRails = false): IndexNode
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
     * @return IndexNode
     */
    public function setTitle(string $title): IndexNode
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
     * @return IndexNode
     */
    public function setTag(string $tag): IndexNode
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
     * @return IndexNode
     */
    public function setDescription(string $description): IndexNode
    {
        $this->description = $description;
        
        return $this;
    }
    
    /**
     * Returns true, if this page should show up in the site map, false if not
     *
     * @return bool
     */
    public function isAddToSiteMap(): bool
    {
        return $this->addToSiteMap;
    }
    
    /**
     * Can be set to true to add the element to the generated sitemap.xml
     *
     * @param   bool  $addToSiteMap
     *
     * @return IndexNode
     */
    public function setAddToSiteMap(bool $addToSiteMap): IndexNode
    {
        $this->addToSiteMap = $addToSiteMap;
        
        return $this;
    }
    
    /**
     * Returns the currently set timestamp of this node
     *
     * @return \DateTime
     */
    public function getTimestamp(): DateTime
    {
        if ($this->timestamp === null) {
            return $this->context->getToday();
        }
        
        return $this->timestamp;
    }
    
    /**
     * Can be used to set the timestamp for this node.
     * This can be either a future date (events) or a past event (news, blog, ...)
     * This value is used to determine the importance of this node
     *
     * @param   \DateTime|null  $timestamp
     *
     * @return IndexNode
     */
    public function setTimestamp(?DateTime $timestamp): IndexNode
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
    public function setLink(?string $linkSet = null, ?iterable $args = [], ?iterable $fragmentArgs = []): IndexNode
    {
        $this->link = $this->context->getLinkService()->getLink($linkSet, $args, $fragmentArgs);
        
        return $this;
    }
    
    /**
     * As the link object is immutable you may use this method by supplying a closure which can be used
     * to modify the link instance. The callback MUST return a new typo link instance that will be used for this node.
     *
     * @param   callable  $editor
     *
     * @return $this
     * @throws \LaborDigital\T3SAI\Exception\SearchAndIndexException
     */
    public function editLink(callable $editor): IndexNode
    {
        $this->link = $editor($this->getLink());
        
        return $this;
    }
    
    /**
     * Returns the link instance of this node.
     * Note: The typo link instance is immutable!
     *
     * @return \LaborDigital\Typo3BetterApi\Link\TypoLink
     */
    public function getLink(): TypoLink
    {
        if ($this->link === null) {
            $this->setLink();
        }
        
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
     * @return IndexNode
     */
    public function setHardLink(?string $hardLink): IndexNode
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
     * @return IndexNode
     */
    public function setImage($image): IndexNode
    {
        $this->image = $image;
        
        return $this;
    }
    
    
    /**
     * Returns the domain key this node is linked to
     *
     * @return string
     */
    public function getDomainKey(): string
    {
        return $this->domainKey;
    }
    
    /**
     * Sets the domain key this node is linked to
     *
     * @param   string  $domainKey
     *
     * @return IndexNode
     */
    public function setDomainKey(string $domainKey): IndexNode
    {
        $this->domainKey = $domainKey;
        
        return $this;
    }
    
    /**
     * Can be used to add any kind of additional data that will be stored alongside the node in the database.
     * It can be used to store information for the output of the search result.
     *
     * @param   mixed  $data  The data to be attached to this node. It has to be JSON-serializable!
     *
     * @return \LaborDigital\T3SAI\Indexer\IndexNode
     */
    public function setMetaData($data): IndexNode
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
    
    /**
     * Returns the prepared database data for the search repository
     *
     * @return array
     * @throws \LaborDigital\T3SAI\Exception\Indexer\IndexNodeException
     * @internal
     */
    public function getPersistedData(): array
    {
        // Validate title
        if (empty($this->getTitle())) {
            throw IndexNodeException::makeInstance($this, 'Error while creating node (' . $this->getGuid() . '), there was no title defined!');
        }
        
        // Parse dynamic content
        $parser = $this->context->getContentParser();
        $link   = $parser->stringifyNodeLink($this, $this->context);
        $image  = $parser->stringifyNodeImage($this, $this->context);
        
        // Parse natural language content
        $title           = $parser->parseInputList([[$this->getTitle(), 50]]);
        $description     = $parser->parseInputList([[$this->getDescription(), 20]]);
        $contents        = $parser->parseInputList($this->contents);
        $keywords        = $parser->parseInputList($this->keywords);
        $displayableText = implode(' [...] ', array_filter([$title['text'], $description['text'], $contents['text']]));
        $words           = $parser->processWordLists(
            $this->context->getLanguage()->getStopWordChecker(),
            $keywords['words'], $title['words'], $description['words'], $contents['words']
        );
        
        // Calculate node priority
        $nodePriority = $parser->calculateNodePriority($this, $words);
        
        // Generate the node row
        $nodeRow = [
            'id'             => $this->getGuid(),
            'url'            => $link,
            'title'          => substr($title['text'], 0, 1024),
            'description'    => $description['text'],
            'image'          => $image,
            'lang'           => $this->context->getLanguage()->getLanguageId(),
            'tag'            => substr($this->getTag(), 0, 256),
            'content'        => $displayableText,
            'set_keywords'   => $keywords['text'],
            'priority'       => $nodePriority,
            'add_to_sitemap' => $this->isAddToSiteMap() ? '1' : '0',
            'timestamp'      => (new DateTimy($this->getTimestamp()))->formatSql(),
            'active'         => '0',
            'domain'         => substr($this->getDomainKey(), 0, 256),
            'meta_data'      => json_encode($this->getMetaData(), JSON_THROW_ON_ERROR),
        ];
        
        // Generate the rows for the words
        $wordRows = [];
        foreach ($words as $word => $priority) {
            $word       = (string)$word;
            $wordRows[] = [
                'tag'      => substr($this->getTag(), 0, 256),
                'lang'     => $this->context->getLanguage()->getLanguageId(),
                'id'       => $this->getGuid(),
                'word'     => substr($word, 0, 256),
                'priority' => $priority,
                'active'   => '0',
                'domain'   => substr($this->getDomainKey(), 0, 256),
                'soundex'  => soundex($word),
            ];
        }
        
        // Allow filtering
        $this->context->getEventBus()->dispatch(($e = new IndexNodeDbDataFilterEvent($this->context, $nodeRow, $wordRows)));
        
        // Finalize our result
        return [
            SearchRepository::TABLE_NODES => $e->getNodeRow(),
            SearchRepository::TABLE_WORDS => $e->getWordRows(),
        ];
    }
}
