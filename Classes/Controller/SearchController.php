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
 * Last modified: 2022.04.13 at 15:38
 */

declare(strict_types=1);


namespace LaborDigital\T3sai\Controller;


use LaborDigital\T3ba\ExtBase\Controller\BetterContentActionController;
use LaborDigital\T3ba\ExtConfig\ExtConfigContext;
use LaborDigital\T3ba\ExtConfigHandler\ExtBase\ContentElement\ConfigureContentElementInterface;
use LaborDigital\T3ba\ExtConfigHandler\ExtBase\ContentElement\ContentElementConfigurator;
use LaborDigital\T3ba\Tool\BackendPreview\BackendPreviewRendererContext;
use LaborDigital\T3ba\Tool\BackendPreview\BackendPreviewRendererInterface;
use LaborDigital\T3ba\Tool\Tca\ContentType\Builder\ContentType;
use LaborDigital\T3sai\Core\Lookup\Paginator\SearchResultPaginator;
use LaborDigital\T3sai\Domain\Repository\SearchRepository;
use LaborDigital\T3sai\FormEngine\UserFunc\SearchDomainItemProvider;

class SearchController extends BetterContentActionController
    implements ConfigureContentElementInterface, BackendPreviewRendererInterface
{
    /**
     * @var \LaborDigital\T3sai\Domain\Repository\SearchRepository
     */
    protected $searchRepository;
    
    public function __construct(SearchRepository $searchRepository)
    {
        $this->searchRepository = $searchRepository;
    }
    
    /**
     * @inheritDoc
     */
    public static function configureContentElement(ContentElementConfigurator $configurator, ExtConfigContext $context): void
    {
        $configurator->setActions(['input', 'apply', 'list'])
                     ->setTitle('t3saibe.p.input.title')
                     ->setDescription('t3saibe.p.input.desc')
                     ->setNoCacheActions(['apply', 'list']);
        
        $configurator->getVariant('list')
                     ->setTitle('t3saibe.p.list.title')
                     ->setDescription('t3saibe.p.list.desc')
                     ->setActions(['list', 'input'])
                     ->setNoCacheActions(['list']);
    }
    
    /**
     * @inheritDoc
     */
    public static function configureContentType(ContentType $type, ExtConfigContext $context): void
    {
        $type->getTab(0)->addMultiple(static function () use ($type) {
            $type->getField('list_page')
                 ->setLabel('t3saibe.p.input.field.listPage')
                 ->setDescription('t3saibe.p.input.field.listPage.desc')
                 ->applyPreset()->relationPage(['maxItems' => 1]);
            
            $type->getField('search_domain')
                 ->setLabel('t3saibe.p.common.field.searchDomain')
                 ->setDescription('t3saibe.p.common.field.searchDomain.desc')
                 ->applyPreset()->select([], [
                    'userFunc' => SearchDomainItemProvider::class . '->provideDomains',
                ]);
        });
    }
    
    public static function configureListContentType(ContentType $type, ExtConfigContext $context): void
    {
        $type->getTab(0)->addMultiple(static function () use ($type) {
            $type->getField('items_per_page')
                 ->setLabel('t3saibe.p.list.field.itemsPerPage')
                 ->applyPreset()->input(['int', 'default' => '20']);
            
            $type->getField('show_tag_filter')
                 ->setLabel('t3saibe.p.list.field.showTagFilter')
                 ->applyPreset()->checkbox(['toggle']);
            
            $type->getField('search_domain')
                 ->setLabel('t3saibe.p.common.field.searchDomain')
                 ->setDescription('t3saibe.p.common.field.searchDomain.desc')
                 ->applyPreset()->select([], [
                    'userFunc' => SearchDomainItemProvider::class . '->provideDomains',
                ]);
        });
    }
    
    public function inputAction()
    {
    }
    
    public function applyAction(?string $queryString = null)
    {
        $data = $this->getData();
        $args = ['queryString' => $queryString];
        
        if (! empty($data['search_domain'])) {
            $args['search_domain'] = $data['search_domain'];
        }
        
        if (! empty($data['list_page'])) {
            $this->redirectToUri(
                $this->getLink('t3saiSearchList', $args)
                     ->withPid((int)$data['list_page'])
                     ->build(['relative'])
            );
        }
        
        $this->forward('list', null, null, $args);
    }
    
    public function listAction(
        ?string $queryString = null,
        ?int $page = null,
        ?string $domainIdentifier = null,
        ?string $tag = null
    )
    {
        if (! $queryString) {
            $this->forward('input');
        }
        
        $data = $this->getData();
        $domainIdentifier = $domainIdentifier ?? $data['search_domain'] ?? null;
        $itemsPerPage = max(1, (int)($data['items_per_page'] ?? 20));
        
        $options = [];
        if ($domainIdentifier) {
            $options['domain'] = $domainIdentifier;
        }
        
        if (! empty($tag)) {
            $options['tags'] = [$tag];
        }
        
        $this->view->assignMultiple([
            'queryString' => $queryString,
            'tag' => $tag,
            'items' => $this->makeInstance(SearchResultPaginator::class, [$queryString, $options, $page, $itemsPerPage]),
            'counts' => $this->searchRepository->findSearchCounts($queryString, $options),
            'domainIdentifier' => $domainIdentifier,
            'showFilter' => (int)$data['show_tag_filter'] === 1,
        ]);
    }
    
    /**
     * @inheritDoc
     */
    public function renderBackendPreview(BackendPreviewRendererContext $context)
    {
        if ($context->getVariant() === 'list') {
            return $context->getUtils()->renderFieldList(['search_domain', 'items_per_page', 'show_tag_filter']);
        }
        
        return $context->getUtils()->renderFieldList(['search_domain']);
    }
    
    
}