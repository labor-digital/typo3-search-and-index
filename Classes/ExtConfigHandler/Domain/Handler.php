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
 * Last modified: 2022.04.11 at 12:23
 */

declare(strict_types=1);


namespace LaborDigital\T3sai\ExtConfigHandler\Domain;


use LaborDigital\T3ba\ExtConfig\Abstracts\AbstractGroupExtConfigHandler;
use LaborDigital\T3ba\ExtConfig\Interfaces\SiteBasedHandlerInterface;
use LaborDigital\T3sai\ExtConfigHandler\Domain\Internal\IndexerList;
use LaborDigital\T3sai\ExtConfigHandler\Domain\Internal\LanguageClassList;
use LaborDigital\T3sai\ExtConfigHandler\Domain\Internal\SiteMapConfigurator;
use LaborDigital\T3sai\Search\Indexer\Page\ContentElement\ContentElementIndexerInterface;
use LaborDigital\T3sai\Search\Indexer\RecordIndexerInterface;
use LaborDigital\T3sai\Soundex\SoundexGeneratorInterface;
use LaborDigital\T3sai\StopWords\StopWordListInterface;
use Neunerlei\Configuration\Handler\HandlerConfigurator;

class Handler extends AbstractGroupExtConfigHandler implements SiteBasedHandlerInterface
{
    /**
     * The list of registered search domains by their domain identifier
     *
     * @var \LaborDigital\T3sai\ExtConfigHandler\Domain\DomainConfigurator[]
     */
    protected $configurators = [];
    
    /**
     * The Configurator to use for the current domain
     *
     * @var \LaborDigital\T3sai\ExtConfigHandler\Domain\DomainConfigurator
     */
    protected $configurator;
    
    /**
     * @inheritDoc
     */
    public function configure(HandlerConfigurator $configurator): void
    {
        $configurator->registerInterface(ConfigureSearchDomainInterface::class);
        $this->registerDefaultLocation($configurator);
    }
    
    /**
     * @inheritDoc
     */
    protected function getGroupKeyOfClass(string $class): string
    {
        /** @var \LaborDigital\T3sai\ExtConfigHandler\Domain\ConfigureSearchDomainInterface $class */
        return $class::getDomainIdentifier();
    }
    
    /**
     * @inheritDoc
     */
    public function prepareHandler(): void { }
    
    /**
     * @inheritDoc
     */
    public function finishHandler(): void
    {
        $state = $this->context->getState();
        foreach ($this->configurators as $configurator) {
            $state->useNamespace('t3sai.domains', function ($state) use ($configurator) {
                $configurator->finish($state);
            });
        }
        $this->configurators = [];
    }
    
    /**
     * @inheritDoc
     */
    public function prepareGroup(string $groupKey, array $groupClasses): void
    {
        $this->configurator = $this->resolveConfigurator($groupKey);
    }
    
    /**
     * @inheritDoc
     */
    public function handleGroupItem(string $class): void
    {
        /** @var \LaborDigital\T3sai\ExtConfigHandler\Domain\ConfigureSearchDomainInterface $class */
        $class::configureSearchDomain($this->configurator, $this->context);
    }
    
    /**
     * @inheritDoc
     */
    public function finishGroup(string $groupKey, array $groupClasses): void
    {
        $this->configurator = null;
    }
    
    /**
     * Returns (and if necessary creates) the correct configurator instance for the provided domain identifier
     *
     * @param   string  $domainIdentifier
     *
     * @return \LaborDigital\T3sai\ExtConfigHandler\Domain\DomainConfigurator
     * @throws \LaborDigital\T3ba\ExtConfig\ExtConfigException
     */
    protected function resolveConfigurator(string $domainIdentifier): DomainConfigurator
    {
        if (isset($this->configurators[$domainIdentifier])) {
            return $this->configurators[$domainIdentifier];
        }
        
        $di = $this->context->getTypoContext()->di();
        
        $c = $di->makeInstance(DomainConfigurator::class, [
            $domainIdentifier,
            $this->context,
            $di->makeInstance(IndexerList::class, [RecordIndexerInterface::class]),
            $di->makeInstance(IndexerList::class, [ContentElementIndexerInterface::class]),
            $di->makeInstance(SiteMapConfigurator::class),
            $di->makeInstance(LanguageClassList::class, [StopWordListInterface::class]),
            $di->makeInstance(LanguageClassList::class, [SoundexGeneratorInterface::class]),
        ]);
        
        return $this->configurators[$domainIdentifier] = $c;
    }
}