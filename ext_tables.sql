--
-- Table structure for table 'tx_search_and_index_nodes'
--
CREATE TABLE `tx_search_and_index_nodes`
(
	`id`           char(36)      DEFAULT ''  NOT NULL,
	`title`        varchar(1024) DEFAULT ''  NOT NULL,
	`description`  text                      NOT NULL,
	`url`          varchar(2048) DEFAULT ''  NOT NULL,
	`image`        varchar(2048) DEFAULT ''  NOT NULL,
	`image_source` varchar(256)  DEFAULT ''  NOT NULL,
	`lang`         tinyint(4) DEFAULT '0' NOT NULL,
	`tag`          varchar(256)  DEFAULT ''  NOT NULL,
	`content`      text                      NOT NULL,
	`set_keywords` text                      NOT NULL,
	`priority`     float         DEFAULT '0' NOT NULL,
	`timestamp`    datetime      DEFAULT NULL,
	`domain`       varchar(256)  DEFAULT ''  NOT NULL,
	`site`         varchar(256)  DEFAULT ''  NOT NULL,
	`active`       tinyint(1) DEFAULT '0' NOT NULL,
	`meta_data`    MEDIUMTEXT                NOT NULL,

	PRIMARY KEY (`id`),
	INDEX          `tag` (`tag`),
	FULLTEXT       INDEX `content` (`content`),
	FULLTEXT       INDEX `set_keywords` (`set_keywords`)
);

--
-- Table structure for table 'tx_search_and_index_words'
--
CREATE TABLE `tx_search_and_index_words`
(
	`id`         char(36)     DEFAULT ''  NOT NULL,
	`word`       varchar(256) DEFAULT ''  NOT NULL,
	`priority`   float        DEFAULT '0' NOT NULL,
	`active`     tinyint(1) DEFAULT '0' NOT NULL,
	`is_keyword` tinyint(1) DEFAULT '0' NOT NULL,
	`tag`        varchar(256) DEFAULT ''  NOT NULL,
	`lang`       tinyint(4) DEFAULT '0' NOT NULL,
	`domain`     varchar(256) DEFAULT ''  NOT NULL,
	`site`       varchar(256) DEFAULT ''  NOT NULL,
	`soundex`    varchar(64)  DEFAULT ''  NOT NULL,
	INDEX        `id` (`id`),
	INDEX        `tag` (`tag`),
	FULLTEXT     INDEX `word` (`word`)
);

--
-- Table structure for table 'tx_search_and_index_sitemaps'
--
CREATE TABLE `tx_search_and_index_sitemaps`
(
	`id`      char(36) DEFAULT '' NOT NULL,
	`sitemap` longtext            NOT NULL,

	PRIMARY KEY (`id`),
);