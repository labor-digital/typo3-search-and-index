# TYPO3 - Search and Index

This package provides a powerful, facetable, language- and domainaware PHP based Search implementation. The search is performed using an intelligent SQL query
to perform complex, fuzzy searches in milli-seconds. To provide high performance, the search is executed on an index of all your contents in your TYPO3
installation. The search can handle custom record types with ease using a class based, extendable indexing algorithm. The indexing of pages is already
implemented with its own indexer task.

Based on the index you can also generate a complete xml sitemap.

## Requirements

- TYPO3 v10
- [TYPO3 - Better API](https://github.com/labor-digital/typo3-better-api)
- Installation using Composer

## Installation

Install this package using composer:

```
composer require labor-digital/typo3-search-and-index
```

After that, you can activate the extension in the Extensions-Manager of your TYPO3 installation

## Documentation

You can find a documented implementation in the [T3SAI branch](https://github.com/labor-digital/typo3-better-api-example/tree/feat-search-and-index) of the T3BA
example repository.

- [Take a look on how indexers are written](https://github.com/labor-digital/typo3-better-api-example/tree/feat-search-and-index/Classes/Search)
- [See how you create search domains](https://github.com/labor-digital/typo3-better-api-example/blob/feat-search-and-index/Configuration/ExtConfig/Site/Main/SearchAndIndex.php)

### Running the indexer

To run the indexer, either use the "t3sai:indexer" cli command, or create a new Scheduler task using the "Search and Index - Indexer" class. You can also run
the indexer by using the "LaborDigital\T3sai\Core\Indexer\Indexer" class in your code.

### Provided routes

- GET /t3sai/sitemap.xml Provides the sitemap (based on the requesting hostname), generated based on the search index.
- POST /t3sai/autocomplete Provides an endpoint to generate Ajax autocomplete features. Simply POST your request with your input string as "search" (form-data)
  and receive the list of autocomplete results as JSON. You might also send a domainIdentifier as "domain" to narrow down the autocomplete results.

### T3FA integration

If you are using the [TYPO3 Frontend API extension](https://github.com/labor-digital/typo3-frontend-api)
the [Provided routes](#provided-routes) will automatically be disabled!

Instead, you can utilize the api capabilities provided by T3FA when running searches, deploying the sitemap.xml or resolving autocomplete results.

#### Search Bundle

The search bundle includes two resources which can be used to access the search index, completely through the api.

Register the bundle like this:

```php
<?php

namespace Vendor\Extension\Configuration\ExtConfig\Site\Main;

use LaborDigital\T3fa\ExtConfigHandler\Api\BundleCollector;
use LaborDigital\T3fa\ExtConfigHandler\Api\ConfigureApiInterface;
use LaborDigital\T3sai\Api\Bundle\SearchBundle;

class Api implements ConfigureApiInterface
{
    public static function registerBundles(BundleCollector $collector): void
    {
        $collector->register(SearchBundle::class);
    }
}
```

##### GET /api/resources/t3sai_search

This route can be used to retrieve search results for a provided input string. You can use the default "page" arguments to paginate the results.

Additionally, there are the following arguments:

- input (string) **REQUIRED**: The urlencoded input string to find the results for
- L (int|string): Either a two char iso language code, or a sys language uid to find the results for. If omitted, the current frontend langauge will be used.
- tags (string): A comma separated list of tags in which the results must be findable
- domain (string): The identifier of a specific search domain to find the results in
- site (string): By default, the current TYPO3 site will be used for the search. This option can be used to target a specific site.
- maxTagItems (int): A limiter of how many entries PER TAG should be returned
- contentMatchLength (int): Defines how many chars the "contentMatch" field can contain in the results
- with (string): A comma separated list of additional flags while resolving the results.
    - count: If given, the list of item counts per tag in the result will be returned, in the "meta" array.
    - tags: If given a list of available tags and their translations will be returned.

##### GET /api/resources/t3sai_autocomplete

This route can be used to retrieve autocomplete suggestions for a provided input string. You can use the default "page" arguments to paginate the results.

If the last char of the input is a space (" ") the suggestions for the next word will be returned, If the last char of the input is a normal character, the
suggestions to complete the current word will be returned.

Additionally, there are the following arguments:

- input (string) **REQUIRED**: The urlencoded input string to find the results for
- L (int|string): Either a two char iso language code, or a sys language uid to find the results for. If omitted, the current frontend langauge will be used.
- domain (string): The identifier of a specific search domain to find the results in
- site (string): By default, the current TYPO3 site will be used for the search. This option can be used to target a specific site.

#### Sitemap Bundle

The sitemap bundle provides the /api/sitemap.xml route

Register the bundle like this:

```php
<?php

namespace Vendor\Extension\Configuration\ExtConfig\Site\Main;

use LaborDigital\T3fa\ExtConfigHandler\Api\BundleCollector;
use LaborDigital\T3fa\ExtConfigHandler\Api\ConfigureApiInterface;
use LaborDigital\T3sai\Api\Bundle\SitemapBundle;

class Api implements ConfigureApiInterface
{
    public static function registerBundles(BundleCollector $collector): void
    {
        $collector->register(SitemapBundle::class);
    }
}
```

##### GET /api/sitemap.xml

Returns the XML sitemap (based on the requesting hostname), generated based on the search index.

## Postcardware

You're free to use this package, but if it makes it to your production environment we highly appreciate you sending us a postcard from your hometown, mentioning
which of our package(s) you are using.

Our address is: LABOR.digital - Fischtorplatz 21 - 55116 Mainz, Germany

We publish all received postcards on our [company website](https://labor.digital). 
