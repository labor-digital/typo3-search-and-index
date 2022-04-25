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
 * Last modified: 2022.04.11 at 14:03
 */

declare(strict_types=1);


namespace LaborDigital\T3sai\Core\Indexer\Node;


use LaborDigital\T3ba\Tool\OddsAndEnds\SerializerUtil;
use LaborDigital\T3sai\Core\Indexer\Node\Converter\ImageConverter;
use LaborDigital\T3sai\Core\Indexer\Node\Converter\LinkConverter;
use LaborDigital\T3sai\Core\Indexer\Node\Converter\PriorityCalculator;
use LaborDigital\T3sai\Core\Indexer\Node\Converter\TextConverter;
use LaborDigital\T3sai\Core\Indexer\Node\Converter\WordExtractor;
use LaborDigital\T3sai\Core\Soundex\SoundexGeneratorFactory;
use LaborDigital\T3sai\Core\StopWords\StopWordListFactory;
use LaborDigital\T3sai\Event\IndexNodeDbDataFilterEvent;
use LaborDigital\T3sai\Exception\Indexer\IndexerException;
use Neunerlei\TinyTimy\DateTimy;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\SingletonInterface;

class NodeConverter implements SingletonInterface
{
    /**
     * @var \LaborDigital\T3sai\Core\Indexer\Node\Converter\TextConverter
     */
    protected $textConverter;
    
    /**
     * @var \LaborDigital\T3sai\Core\Indexer\Node\Converter\LinkConverter
     */
    protected $linkConverter;
    
    /**
     * @var \LaborDigital\T3sai\Core\Indexer\Node\Converter\WordExtractor
     */
    protected $wordExtractor;
    
    /**
     * @var \LaborDigital\T3sai\Core\Indexer\Node\Converter\ImageConverter
     */
    protected $imageConverter;
    
    /**
     * @var \LaborDigital\T3sai\Core\Indexer\Node\Converter\PriorityCalculator
     */
    protected $priorityCalculator;
    
    /**
     * @var \LaborDigital\T3sai\Core\StopWords\StopWordListFactory
     */
    protected $stopWordListFactory;
    
    /**
     * @var \Psr\EventDispatcher\EventDispatcherInterface
     */
    protected $eventDispatcher;
    
    /**
     * @var \LaborDigital\T3sai\Core\Soundex\SoundexGeneratorFactory
     */
    protected $soundexGeneratorFactory;
    
    /**
     * @var \LaborDigital\T3sai\Core\Soundex\SoundexGeneratorInterface
     */
    protected $soundexGenerator;
    
    public function __construct(
        TextConverter $contentConverter,
        LinkConverter $linkConverter,
        WordExtractor $wordExtractor,
        ImageConverter $imageConverter,
        PriorityCalculator $priorityCalculator,
        StopWordListFactory $stopWordListFactory,
        EventDispatcherInterface $eventDispatcher,
        SoundexGeneratorFactory $soundexGeneratorFactory
    )
    {
        $this->textConverter = $contentConverter;
        $this->linkConverter = $linkConverter;
        $this->wordExtractor = $wordExtractor;
        $this->imageConverter = $imageConverter;
        $this->priorityCalculator = $priorityCalculator;
        $this->stopWordListFactory = $stopWordListFactory;
        $this->eventDispatcher = $eventDispatcher;
        $this->soundexGeneratorFactory = $soundexGeneratorFactory;
    }
    
    public function convertToArray(Node $node): array
    {
        $this->validateTitle($node);
        
        $this->soundexGenerator = $this->soundexGeneratorFactory
            ->makeSoundexGenerator($node->getDomainConfig(), $node->getLanguage()->getTwoLetterIsoCode());
        
        $textList = $this->generateTextList($node);
        $wordList = $this->generateWordList($node, $textList);
        
        $nodeRow = $this->makeNodeRow($node, $textList, $wordList);
        $wordRows = $this->makeWordRows($node, $wordList);
        
        $e = $this->eventDispatcher->dispatch(
            new IndexNodeDbDataFilterEvent($node->getRequest(), $nodeRow, $wordRows)
        );
        
        return [$e->getNodeRow(), $e->getWordRows()];
    }
    
    protected function validateTitle(Node $node): void
    {
        if (empty($node->getTitle())) {
            throw new IndexerException('Error while creating node, there was no title defined!');
        }
    }
    
    protected function generateTextList(Node $node): array
    {
        return [
            'content' => $this->textConverter->convertText($node->getContents()),
            'keywords' => $this->textConverter->convertText($node->getKeywords()),
            'title' => $this->textConverter->convertText([[$node->getTitle(), 50]]),
            'description' => $this->textConverter->convertText([[$node->getDescription(), 20]]),
        ];
    }
    
    protected function generateWordList(Node $node, array $textList): array
    {
        return $this->priorityCalculator->calculateWordPriority(
            $this->wordExtractor->extractWords(
                $this->stopWordListFactory->makeStopWordList($node->getDomainConfig(), $node->getLanguage()->getTwoLetterIsoCode()),
                $textList
            )
        );
    }
    
    protected function makeNodeRow(Node $node, array $textList, array $wordList): array
    {
        return $this->initializeDbRow($node, [
            'url' => $this->linkConverter->convertLink($node->getHardLink(), $node->getLink()),
            'title' => $this->generateTitle($textList),
            'description' => $this->textConverter->mergeTexts($textList['description']),
            'image' => $this->imageConverter->convertImageToLink($node->getImage()),
            'image_source' => $this->imageConverter->convertImageToSource($node->getImage()),
            'content' => $this->generateContent($textList),
            'set_keywords' => $this->textConverter->mergeTexts($textList['keywords']),
            'priority' => $this->priorityCalculator->calculateNodePriority($node->getTimestamp(), $node->getPriority(), $wordList),
            'timestamp' => (new DateTimy($node->getTimestamp()))->formatSql(),
            'meta_data' => $node->getMetaData() === null ? '' : SerializerUtil::serializeJson($node->getMetaData()),
        ]);
    }
    
    protected function makeWordRows(Node $node, array $wordList): array
    {
        $rows = [];
        
        foreach ($wordList as $word => $priority) {
            $rows[] = $this->initializeDbRow($node, [
                'word' => substr($word, 0, 256),
                'priority' => $priority,
                'soundex' => $this->soundexGenerator->generate($word),
            ]);
        }
        
        return $rows;
    }
    
    protected function initializeDbRow(Node $node, array $mergeFields): array
    {
        return array_merge(
            [
                'id' => $node->getGuid(),
                'lang' => $node->getLanguage()->getLanguageId(),
                'tag' => substr($node->getTag(), 0, 256),
                'domain' => substr($node->getDomainIdentifier(), 0, 256),
                'site' => substr($node->getSite()->getIdentifier(), 0, 256),
                'active' => '0',
            ],
            $mergeFields
        );
    }
    
    protected function generateTitle(array $textList): string
    {
        return substr($textList['title'][0][0] ?? '', 0, 1024);
    }
    
    protected function generateContent(array $textList): string
    {
        return $this->textConverter->mergeTexts(
            $textList['title'],
            $textList['description'],
            $textList['content']
        );
    }
}