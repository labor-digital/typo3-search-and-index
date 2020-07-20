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
 * Last modified: 2020.07.16 at 18:48
 */

declare(strict_types=1);
/**
 * Copyright 2019 LABOR.digital
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
 * Last modified: 2019.03.25 at 13:36
 */

namespace LaborDigital\T3SAI\Builtin\Transformer\News;


use LaborDigital\T3SAI\Configuration\SearchDomainConfig;
use LaborDigital\T3SAI\Event\NewsIndexNodeFilterEvent;
use LaborDigital\T3SAI\Indexer\IndexerContext;
use LaborDigital\T3SAI\Indexer\IndexNode;
use LaborDigital\T3SAI\Indexer\Transformer\AbstractRecordTransformer;
use LaborDigital\Typo3BetterApi\Domain\Repository\BetterRepository;
use LaborDigital\Typo3BetterApi\Link\TypoLink;

class NewsTransformer extends AbstractRecordTransformer
{
    
    /**
     * @var \GeorgRinger\News\Domain\Repository\NewsRepository|mixed
     */
    protected $repository;
    
    /**
     * @var \LaborDigital\T3SAI\Configuration\SearchDomainConfig
     */
    protected $config;
    
    /**
     * NewsTransformer constructor.
     *
     * @param   \LaborDigital\T3SAI\Configuration\SearchDomainConfig  $config
     */
    public function __construct(SearchDomainConfig $config)
    {
        $this->config = $config;
    }
    
    /**
     * @inheritDoc
     */
    public function resolve(IndexerContext $context): iterable
    {
        $newsConfig = $this->config->getNewsTransformerConfig();
        
        // Create the repository instance
        $this->repository = $this->getSingletonOf($newsConfig->repositoryClass);
        
        // Transform pid fields into their real, numeric values
        $pids      = [];
        $pidAspect = $this->TypoContext()->Pid();
        $pidParser = static function (array $list) use (&$pids, $pidAspect): array {
            $clean = [];
            foreach ($list as $pid => $v) {
                $pid         = $pidAspect->has($pid) ? $pidAspect->get($pid) : $pid;
                $clean[$pid] = $v;
                $pids[]      = $pid;
            }
            
            return $clean;
        };
        
        // Resolve the pids
        $newsConfig->storagePids                            = array_keys($pidParser(array_fill_keys($newsConfig->storagePids, null)));
        $newsConfig->storagePidToTagMap                     = $pidParser($newsConfig->storagePidToTagMap);
        $newsConfig->storagePidToLinkSetMap                 = $pidParser($newsConfig->storagePidToLinkSetMap);
        $newsConfig->storagePidsToHideOnSiteMap             = $pidParser($newsConfig->storagePidsToHideOnSiteMap);
        $newsConfig->storagePidsToIgnoreInTimestampPriority = $pidParser($newsConfig->storagePidsToIgnoreInTimestampPriority);
        $newsConfig->storagePidToDetailPidMap               = $pidParser($newsConfig->storagePidToDetailPidMap);
        $pidsClean                                          = array_unique($pids);
        
        // Create query object
        $query = BetterRepository::getWrapper($this->repository)->getQuery();
        $query = $query->withPids($pidsClean);
        
        return $query->getAll();
    }
    
    /**
     * @inheritDoc
     */
    public function convert($element, IndexNode $node, IndexerContext $context): void
    {
        $newsConfig = $this->config->getNewsTransformerConfig();
        
        /** @var \GeorgRinger\News\Domain\Model\News $element */
        $tag = $newsConfig->storagePidToTagMap[$element->getPid()] ?: "news";
        $node->setTag($tag);
        $node->setTitle($element->getTitle());
        $node->setDescription($element->getTeaser());
        $node->setTimestamp($element->getDatetime());
        $node->setImage($element->getFalMedia());
        
        // Build link
        if (isset($newsConfig->storagePidToLinkSetMap[$element->getPid()])) {
            // Build link based on link set
            $linkSet = $newsConfig->storagePidToLinkSetMap[$element->getPid()];
            $node->setLink($linkSet, ["news" => $element]);
        } else {
            // Build pid based link
            $node->setLink();
            $node->editLink(static function (TypoLink $link) use ($newsConfig, $element) {
                $detailPid = $newsConfig->storagePidToDetailPidMap[$element->getPid()] ?? $newsConfig->detailPid;
                
                // Build link
                return $link
                    ->withControllerClass($newsConfig->linkControllerClass)
                    ->withPluginName($newsConfig->linkPluginName)
                    ->withControllerAction($newsConfig->linkControllerAction)
                    ->withPid($detailPid)
                    ->withArgs(["news" => $element]);
            });
        }
        
        // Build content
        $node->addContent((string)$element->getBodytext());
        if (! empty($element->getContentElements())) {
            foreach ($element->getContentElements() as $e) {
                /** @var \GeorgRinger\News\Domain\Model\TtContent $e */
                $node->addContent((string)$e->getHeader());
                $node->addContent((string)$e->getBodytext());
            }
        }
        
        // Calculate time based priority
        if (! in_array($element->getPid(), $newsConfig->storagePidsToIgnoreInTimestampPriority, true)) {
            $dateDiff = $element->getDatetime()->diff($context->getToday());
            $weekLoss = 1;
            $loss     = $dateDiff->days / 7 * $weekLoss;
            $priority = $node->getPriority() - $loss;
            $priority = min(200, max(-200, $priority));
            $node->setPriority($priority, true);
        }
        
        // Check if this node should be hidden on the siteMap
        if (in_array($element->getPid(), $newsConfig->storagePidsToHideOnSiteMap, true)) {
            $node->setAddToSiteMap(false);
        }
        
        // Allow filtering
        $this->EventBus()->dispatch(new NewsIndexNodeFilterEvent(
            $node, $element, $context, $newsConfig
        ));
    }
}
