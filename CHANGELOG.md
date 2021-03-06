# Changelog

All notable changes to this project will be documented in this file. See [standard-version](https://github.com/conventional-changelog/standard-version) for commit guidelines.

### [9.3.2](https://github.com/labor-digital/typo3-search-and-index/compare/v9.3.1...v9.3.2) (2021-05-11)


### Bug Fixes

* **Sitemap:** make sure images have the correct namespace ([c41a6e1](https://github.com/labor-digital/typo3-search-and-index/commit/c41a6e191dce82fe6f2e82d20f7b4bd336ba408f))

### [9.3.1](https://github.com/labor-digital/typo3-search-and-index/compare/v9.3.0...v9.3.1) (2021-01-11)


### Bug Fixes

* **SearchRepository:** fix issues with multi queries ([2f47690](https://github.com/labor-digital/typo3-search-and-index/commit/2f476903461afd02203b2ca464e7091fdc7e72b1))

## [9.3.0](https://github.com/labor-digital/typo3-search-and-index/compare/v9.2.2...v9.3.0) (2020-12-11)


### Features

* implement option to disable nodes in the search index ([858fc33](https://github.com/labor-digital/typo3-search-and-index/commit/858fc335346faab3603a213f8e0ff8f8f1dd1d71))

### [9.2.2](https://github.com/labor-digital/typo3-search-and-index/compare/v9.2.1...v9.2.2) (2020-09-08)


### Bug Fixes

* **SearchRepository:** calculate '[@total](https://github.com/total)' correctly for empty results ([4433eb0](https://github.com/labor-digital/typo3-search-and-index/commit/4433eb032fb94d22f99fbb1ae39b034531091147))

### [9.2.1](https://github.com/labor-digital/typo3-search-and-index/compare/v9.2.0...v9.2.1) (2020-09-04)


### Bug Fixes

* **SearchQueryBuilder:** remove @ from $useOldRowNumberGeneration doc block to prevent missing annotation exception ([0bc0be4](https://github.com/labor-digital/typo3-search-and-index/commit/0bc0be410b890e046b41921a85dfad47df9fb8c4))

## [9.2.0](https://github.com/labor-digital/typo3-search-and-index/compare/v9.1.2...v9.2.0) (2020-09-03)


### Features

* implement better resource pagination + result counting endpoint ([4e063ac](https://github.com/labor-digital/typo3-search-and-index/commit/4e063ac75fe8664280e96ca5b0ec242fc696db31))

### [9.1.2](https://github.com/labor-digital/typo3-search-and-index/compare/v9.1.1...v9.1.2) (2020-08-22)

### [9.1.1](https://github.com/labor-digital/typo3-search-and-index/compare/v9.1.0...v9.1.1) (2020-08-06)

## 9.1.0 (2020-07-20)


### Features

* first, public release ([f16abb4](https://github.com/labor-digital/typo3-search-and-index/commit/f16abb4a664c9a55ee123990b4fc0d9c9f503fa0))

# 4.3.0 (2020-02-04)


### Features

* implement better configuration api (ba8670b)



# 4.2.0 (2020-01-20)


### Features

* update frontend api configuration to match the latest version (fbbd94d)



# 4.1.0 (2019-12-06)


### Features

* update the package to the requirements of a typo3 v9 environment (f23414f)



# 4.0.0 (2019-12-04)


* feat(drop support for Typo v8 and below): (3d465d2)


### BREAKING CHANGES

* drop support for Typo v8 and below



## 3.1.1 (2019-09-09)


### Bug Fixes

* remove remaining x dependencies (d962f8c)



# 3.1.0 (2019-09-07)


### Features

* implement better api change: rename TableConfigInterface->configure to configureTable and make it static (97611b8)



# 3.0.0 (2019-09-06)


### Bug Fixes

* re-enable commented transformer creation (56c633d)
* re-enable commented transformer creation (3a2b502)


### Features

* add support for BetterQuery objects now being immutable (e6254d9)
* major rewrite of the core features (0a4bff5)
* require the labor/typo3-better-api package instead of the old labor/typo3-typo-base package (c9569fe)


### BREAKING CHANGES

* major rewrite of the core features



## 2.4.1 (2019-08-06)



# 2.4.0 (2019-07-16)


### Features

* update class names to match the latest version of the betterApi extension (6906a6b)



## 2.3.1 (2019-07-04)



# 2.3.0 (2019-05-23)


### Features

* add support for latest version of typo base extension (3b1cfbe)



# 2.2.0 (2019-04-10)


### Bug Fixes

* **PageData:** make provider compatible with typo3 v8 (42884f2)
* **SearchRepository:** better handling of short words inside a search query (d979320)


### Features

* **Indexer:** add language white list to define languages to be indexed (aeebad0)
* **SiteMapGenerator:** generate image sitemap for the main image of each entry (3de464a)



# 2.1.0 (2019-03-26)


### Features

* rewritten lookup cycle for more relevant results (27ce24b)



# 2.0.0 (2019-03-25)


### Features

* Rewrite of the indexer API (ba67ef0)


### BREAKING CHANGES

* The API changed quite a bit, so older projects have to
be adjusted to the new class- and event names



## 1.0.1 (2019-03-19)
