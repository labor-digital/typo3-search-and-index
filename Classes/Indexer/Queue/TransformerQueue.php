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
 * Last modified: 2020.07.16 at 21:44
 */

declare(strict_types=1);


namespace LaborDigital\T3SAI\Indexer\Queue;


use LaborDigital\T3SAI\Builtin\Transformer\News\NewsTransformer;
use LaborDigital\T3SAI\Builtin\Transformer\Page\PageTransformer;
use LaborDigital\T3SAI\Domain\Entity\PersistableNodeList;
use LaborDigital\T3SAI\Event\IndexerBeforeConversionEvent;
use LaborDigital\T3SAI\Event\IndexerBeforeResolveEvent;
use LaborDigital\T3SAI\Event\IndexerNodeFilterEvent;
use LaborDigital\T3SAI\Exception\Indexer\InvalidIndexerConfigException;
use LaborDigital\T3SAI\Exception\Indexer\InvalidTransformerException;
use LaborDigital\T3SAI\Indexer\IndexerContext;
use LaborDigital\T3SAI\Indexer\IndexNode;
use LaborDigital\T3SAI\Indexer\Transformer\RecordTransformerInterface;
use Throwable;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class TransformerQueue
{
    
    /**
     * The list of all transformers
     *
     * @var \LaborDigital\T3SAI\Indexer\Transformer\RecordTransformerInterface[]
     */
    protected $transformers;
    
    /**
     * The language queue to iterate for all transformers
     *
     * @var \LaborDigital\T3SAI\Indexer\Queue\LanguageQueue
     */
    protected $languageQueue;
    
    /**
     * The indexer context object
     *
     * @var \LaborDigital\T3SAI\Indexer\IndexerContext
     */
    protected $context;
    
    /**
     * The list of all persistable nodes
     *
     * @var PersistableNodeList
     */
    protected $nodeList;
    
    /**
     * TransformerQueue constructor.
     *
     * @param   \LaborDigital\T3SAI\Indexer\Queue\LanguageQueue  $languageQueue
     * @param   \LaborDigital\T3SAI\Indexer\IndexerContext       $context
     */
    public function __construct(LanguageQueue $languageQueue, IndexerContext $context)
    {
        $this->languageQueue = $languageQueue;
        $this->context       = $context;
    }
    
    /**
     * Processes the list of registered transformers in all registered languages
     * and returns a list of all index nodes which have been resolved in the process
     *
     * @return \LaborDigital\T3SAI\Domain\Entity\PersistableNodeList
     */
    public function process(): PersistableNodeList
    {
        $this->nodeList = $this->context->getContainer()->getWithoutDi(PersistableNodeList::class);
        
        // Iterate over all registered transformers
        foreach ($this->makeTransformerList() as $transformer) {
            $this->context->setTransformer($transformer);
            try {
                // Process all languages
                $this->languageQueue->process(function () use ($transformer) {
                    // Process a single transformer inside a language
                    $this->processSingleTransformer($transformer);
                });
            } catch (Throwable $exception) {
                // Catch errors
                $this->context->logError(
                    'Failed to process a transformer! ' .
                    'Domain: ' . $this->context->getDomainKey() .
                    ', Transformer:' . get_class($transformer) .
                    ', Language: ' . $this->context->getLanguage()->getTitle() .
                    ', Error: ' . $exception->getMessage() .
                    ', File: ' . $exception->getFile() . ' (' . $exception->getLine() . ')',
                    [$exception->getTraceAsString()]
                );
            } finally {
                // Reset the transformer
                $this->context->setTransformer(null);
            }
        }
        
        return $this->nodeList;
    }
    
    /**
     * Handles the resolving of elements of a transformer and iterates the resulting
     * list with the processSingleElement() method in order to build the node list
     *
     * @param   \LaborDigital\T3SAI\Indexer\Transformer\RecordTransformerInterface  $transformer
     */
    public function processSingleTransformer(RecordTransformerInterface $transformer): void
    {
        // Resolve elements
        try {
            // Allow filtering
            $this->context->getEventBus()->dispatch(new IndexerBeforeResolveEvent($this->context));
            
            // Resolve the elements
            $elements = $transformer->resolve($this->context);
            
        } catch (Throwable $exception) {
            // Catch errors
            $this->context->logError(
                'Failed to resolve a element list! ' .
                'Domain: ' . $this->context->getDomainKey() .
                ', Transformer:' . get_class($transformer) .
                ', Language: ' . $this->context->getLanguage()->getTitle() .
                ', Error:' . $exception->getMessage() .
                ', File: ' . $exception->getFile() . ' (' . $exception->getLine() . ')',
                [$exception->getTraceAsString()]
            );
            
            return;
        }
        
        // Process the elements
        try {
            // Allow filtering
            $this->context->getEventBus()->dispatch(
                ($e = new IndexerBeforeConversionEvent($this->context, $elements)));
            $elements = $e->getElements();
            
            // Iterate the elements
            foreach ($elements as $key => $element) {
                $this->processSingleElement($transformer, $key, $element);
            }
            
        } catch (Throwable $exception) {
            // Catch errors
            $this->context->logError(
                'Failed to process a element list! ' .
                'Domain: ' . $this->context->getDomainKey() .
                ', Transformer:' . get_class($transformer) .
                ', Language: ' . $this->context->getLanguage()->getTitle() .
                ', Error:' . $exception->getMessage() .
                ', File: ' . $exception->getFile() . ' (' . $exception->getLine() . ')',
                [$exception->getTraceAsString()]
            );
            
            return;
        }
        
    }
    
    /**
     * Processes a single, resolved element and adds it's node to the list of nodes
     *
     * @param   RecordTransformerInterface  $transformer  The transformer to convert the element with
     * @param   mixed                       $key          The index of the element in the list of elements
     * @param   mixed                       $element      The element to convert into a node
     */
    protected function processSingleElement(RecordTransformerInterface $transformer, $key, $element): void
    {
        try {
            // Create a new node
            $node = $this->context->getContainer()->getWithoutDi(IndexNode::class, [$this->context]);
            $this->context->setNode($node);
            $this->context->setElement($element);
            
            // Convert the element
            $transformer->convert($element, $node, $this->context);
            
            // Filter the node
            $this->context->getEventBus()->dispatch(new IndexerNodeFilterEvent($this->context));
            
            // Store the node
            $this->nodeList->addNode($node);
            
            // Show our success
            $this->context->getLogger()->info(
                'Successfully added a new node! ' .
                'Domain: ' . $this->context->getDomainKey() .
                ', Transformer:' . get_class($transformer) .
                ', Element Type: ' . (is_object($element) ? get_class($element) : gettype($element)) .
                ', Element UID: ' . $this->tryToFindElementUid($element) .
                ', Key: ' . $key .
                ', Language: ' . $this->context->getLanguage()->getTitle()
            );
            
        } catch (Throwable $exception) {
            // Catch errors
            $this->context->logError(
                'Failed to convert an element into a node! ' .
                'Domain: ' . $this->context->getDomainKey() .
                ', Transformer:' . get_class($transformer) .
                ', Element Type: ' . (is_object($element) ? get_class($element) : gettype($element)) .
                ', Element UID: ' . $this->tryToFindElementUid($element) .
                ', Key: ' . $key .
                ', Language: ' . $this->context->getLanguage()->getTitle() .
                ', Error:' . $exception->getMessage() .
                ', File: ' . $exception->getFile() . ' (' . $exception->getLine() . ')',
                [$exception->getTraceAsString()]
            );
        } finally {
            $this->context->setNode(null);
            $this->context->setElement(null);
        }
    }
    
    /**
     * Helper that tries to find the UID property on a given element.
     *
     * @param   array|AbstractEntity|object|\stdClass|mixed  $element  The element to extract the uid from
     *
     * @return string
     */
    protected function tryToFindElementUid($element): string
    {
        $uid = 'unknown';
        if (is_object($element)) {
            if (method_exists($element, 'getUid')) {
                $uid = $element->getUid();
            } elseif (property_exists($element, 'uid')) {
                /** @var mixed $element */
                $uid = $element->uid;
            } /** @noinspection NotOptimalIfConditionsInspection */
            elseif (isset($element['uid'])) {
                $uid = $element['uid'];
            }
        } elseif (is_array($element) && isset($element['uid'])) {
            $uid = $element['uid'];
        }
        
        return (string)$uid;
    }
    
    /**
     * Creates a list of transformer instances that have to be iterated based on the domain configuration
     *
     * @return RecordTransformerInterface[]
     * @throws \LaborDigital\T3SAI\Exception\Indexer\InvalidIndexerConfigException
     * @throws \LaborDigital\T3SAI\Exception\Indexer\InvalidTransformerException
     */
    protected function makeTransformerList(): array
    {
        $domainConfig = $this->context->getDomainConfig();
        $container    = $this->context->getContainer();
        $transformers = [];
        
        // Activate page transformer
        if ($domainConfig->getPageTransformerConfig()->active) {
            $transformers[] = $container->get(PageTransformer::class);
        }
        
        // Activate news transformer
        if ($domainConfig->getNewsTransformerConfig()->active) {
            $transformers[] = $container->get(NewsTransformer::class);
        }
        
        // Activate user transformers
        foreach ($domainConfig->getRecordTransformers() as $className) {
            $i = $container->get($className);
            if (! $i instanceof RecordTransformerInterface) {
                throw new InvalidTransformerException(
                    'The given transformer class $className does not implement the required interface: ' .
                    RecordTransformerInterface::class);
            }
            $transformers[] = $i;
        }
        
        // Fail if there are no transformers
        if (empty($transformers)) {
            throw new InvalidIndexerConfigException(
                'The search domain: ' . $this->context->getDomainKey() . ' has no registered transformers!'
            );
        }
        
        return $transformers;
    }
}
