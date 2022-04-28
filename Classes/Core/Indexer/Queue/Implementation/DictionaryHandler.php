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
 * Last modified: 2022.04.27 at 09:59
 */

declare(strict_types=1);


namespace LaborDigital\T3sai\Core\Indexer\Queue\Implementation;


use LaborDigital\T3sai\Core\Indexer\Dictionary\NerfWordList;
use LaborDigital\T3sai\Core\Indexer\Queue\QueueHandlerInterface;
use LaborDigital\T3sai\Core\Indexer\Queue\QueueRequest;
use LaborDigital\T3sai\Domain\Repository\IndexerRepository;

class DictionaryHandler implements QueueHandlerInterface
{
    /**
     * @var \LaborDigital\T3sai\Core\Indexer\Dictionary\NerfWordList
     */
    protected $nerfWordList;
    
    /**
     * @var \LaborDigital\T3sai\Domain\Repository\IndexerRepository
     */
    protected $repository;
    
    public function __construct(NerfWordList $nerfWordList, IndexerRepository $repository)
    {
        $this->nerfWordList = $nerfWordList;
        $this->repository = $repository;
    }
    
    /**
     * @inheritDoc
     */
    public function handle($item, QueueRequest $request, callable $children): void
    {
        $this->nerfWordList->flush();
        
        $children($request);
        
        $this->repository->applyNerfWords(
            $this->nerfWordList->getAll(),
            $request->getSite()->getIdentifier(),
            $request->getDomainIdentifier(),
            $request->getLanguage()->getTwoLetterIsoCode()
        );
        $this->nerfWordList->flush();
    }
    
    /**
     * @inheritDoc
     */
    public function provideIterable(QueueRequest $request): iterable
    {
        return [null];
    }
    
}