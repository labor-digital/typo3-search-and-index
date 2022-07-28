# Changelog

All notable changes to this project will be documented in this file. See [standard-version](https://github.com/conventional-changelog/standard-version) for commit guidelines.

## [10.3.0](https://github.com/labor-digital/typo3-search-and-index/compare/v10.2.0...v10.3.0) (2022-07-28)


### Features

* make content separator (formerly [...]) configurable for each domain ([1e172ae](https://github.com/labor-digital/typo3-search-and-index/commit/1e172ae812a8b6c2223da5afc1edc1bc7ac7fabd))


### Bug Fixes

* **PageIndexer:** use correct col option for additional title content ([b271fcc](https://github.com/labor-digital/typo3-search-and-index/commit/b271fcccb1c5c7cde70d77d4e0cf0875496d60ea))

## [10.2.0](https://github.com/labor-digital/typo3-search-and-index/compare/v10.1.0...v10.2.0) (2022-07-28)


### Features

* **Indexer:** allow fallback columns in page indexer options ([070aeb9](https://github.com/labor-digital/typo3-search-and-index/commit/070aeb969d10befdb38d0ccc886a3e56b486fabe))

## [10.1.0](https://github.com/labor-digital/typo3-search-and-index/compare/v10.0.1...v10.1.0) (2022-06-22)


### Features

* add extended logging for indexer task ([79ab01e](https://github.com/labor-digital/typo3-search-and-index/commit/79ab01e601fdfd96b3a11aae08fc39eb97964380))

### [10.0.1](https://github.com/labor-digital/typo3-search-and-index/compare/v10.0.0...v10.0.1) (2022-04-29)


### Bug Fixes

* **SearchProcessor:** additional cleanup for rendering the contentMatch ([55a7b1c](https://github.com/labor-digital/typo3-search-and-index/commit/55a7b1cfc8a0f0a7b86271f91a397122b1261c04))

## [10.0.0](https://github.com/labor-digital/typo3-search-and-index/compare/v9.3.2...v10.0.0) (2022-04-29)


### ⚠ BREAKING CHANGES

* Complete rewrite of the logic to be a lot cleaner and
easier to extend through the DI container magic. Breaks a lot of the
current contracts
* Requires TYPO3 v10 Also breaks all APIs to be future
proof

### Features

* bump version to support v10 ([33bf4a6](https://github.com/labor-digital/typo3-search-and-index/commit/33bf4a6d512f570b1e3cf02ce905b1d6ac81d750))
* **Autocomplete:** remove some junk (like inflections) from autocomplete results ([04aaaa4](https://github.com/labor-digital/typo3-search-and-index/commit/04aaaa43be3880051ca64b1e9fa2eb4090b9fbcd))
* **Event:** re-add resource filter events ([32bb344](https://github.com/labor-digital/typo3-search-and-index/commit/32bb3448180252aa444ba1445b2ab71ffc4b291c))
* **Indexer:** calculate "nerfWords" to apply a penalty to common words ([c67886b](https://github.com/labor-digital/typo3-search-and-index/commit/c67886b33c78b9e930bc8ec6cf90abf21d06b7cd))
* **Indexer:** index internal image sources to allow lookup through FAL later ([3fc4a84](https://github.com/labor-digital/typo3-search-and-index/commit/3fc4a84245e83716a8472dc5666c6d991a8ae74c))
* **Lookup:** multiple improvements when retrieving search and autocomplete results ([c9f4950](https://github.com/labor-digital/typo3-search-and-index/commit/c9f4950466a385051755941390cda1a28bfb93fe))
* **Mysql:** use exact match in the "title" of a node as high priority find ([252faea](https://github.com/labor-digital/typo3-search-and-index/commit/252faea282604b4ace3278af935ab4a499d1b0c5))
* **SearchRepository:** return tags including translation labels ([03f4329](https://github.com/labor-digital/typo3-search-and-index/commit/03f43292f9d4157916fcae1d6de97ba1966b947e))
* **SearchResource:** allow setting the "site" parameter via query ([10f5653](https://github.com/labor-digital/typo3-search-and-index/commit/10f56533bc0046993e046d3bdacbe8f26c316559))
* **T3fa:** implement integration into the frontend api ([f47ade7](https://github.com/labor-digital/typo3-search-and-index/commit/f47ade7d7468296ed6feb7e9850e7c4f5f4d314e))
* **Tags:** make fallback labels more readable ([eb0dea1](https://github.com/labor-digital/typo3-search-and-index/commit/eb0dea1a1adcb05477e1daa554d0687a74b91393))
* **TagTranslationProvider:** implement getTagByTranslatedInputLabel utility ([213a61c](https://github.com/labor-digital/typo3-search-and-index/commit/213a61c1a7c443ce777380b6acce367cac681617))
* major refactoring and upgrade for v10 ([33cdaf2](https://github.com/labor-digital/typo3-search-and-index/commit/33cdaf28988973a39bbc993959445dcf6fab77c9))


### Bug Fixes

* **AutocompleteResource:** handle invalid item serialization ([72f52b9](https://github.com/labor-digital/typo3-search-and-index/commit/72f52b9aad362013c72646ef5b5bdc8c11ccc483))
* **AutocompleteResource:** remove not supported "tags" option ([3134f16](https://github.com/labor-digital/typo3-search-and-index/commit/3134f162d50946f7296f7e6e88eabcf7b237b6e0))
* **DomainConfigRepository:** allow NULL value in 'allowedLanguages' ([b074160](https://github.com/labor-digital/typo3-search-and-index/commit/b074160fb19ff03c09c42d42e4da69f0ed34530b))
* **DomainConfigurator:** don't apply array_map when allowedLanguages is null ([650130a](https://github.com/labor-digital/typo3-search-and-index/commit/650130aa4d0a47d181da8c39bcf28a5c6f765f37))
* **ExtConfig:** use the correct interfaces for stoundex and stopword list collectors ([a465574](https://github.com/labor-digital/typo3-search-and-index/commit/a465574bde7f3ff9e9c6cb3907a11878d2f4a77f))
* **GermanSoundexGenerator:** use mb_ string functions where possible ([2b64b1c](https://github.com/labor-digital/typo3-search-and-index/commit/2b64b1c668f1eaae38ad415c6088f944d3932098))
* **GermanStopWords:** ensure umlauts in stopwords are parsed correctly ([7c7d55e](https://github.com/labor-digital/typo3-search-and-index/commit/7c7d55eee3c4c9d6156554923cb3aa0666502b5e))
* **Lookup\RequestFactory:** translate tags provided by options, too ([d3ca6f2](https://github.com/labor-digital/typo3-search-and-index/commit/d3ca6f239480772934cecd28038313f0c1b65a35))
* **ProcessorUtilTrait:** simplify iterableToArray ([b72ea96](https://github.com/labor-digital/typo3-search-and-index/commit/b72ea96af41055b074062bbae7655242c512fc2b))
* **QueueRequest:** return $clone instead of $this in withNode() ([9026bf5](https://github.com/labor-digital/typo3-search-and-index/commit/9026bf5824f44c9472e0756a63dd2d24c5c6cedb))
* **SearchProcessor:** ensure that no empty match patterns are created ([34459c7](https://github.com/labor-digital/typo3-search-and-index/commit/34459c796b45dfbfe83a52a3272f4333d3aff152))
* **SearchRepository:** fix some incorrect documentation parts ([77f6ba1](https://github.com/labor-digital/typo3-search-and-index/commit/77f6ba1ade476b2afc7fec3638b4b3c8e16f730b))

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
