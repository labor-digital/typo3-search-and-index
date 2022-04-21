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

## Postcardware

You're free to use this package, but if it makes it to your production environment we highly appreciate you sending us a postcard from your hometown, mentioning
which of our package(s) you are using.

Our address is: LABOR.digital - Fischtorplatz 21 - 55116 Mainz, Germany

We publish all received postcards on our [company website](https://labor.digital). 
