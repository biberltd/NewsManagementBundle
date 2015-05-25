SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for categories_of_news
-- ----------------------------
DROP TABLE IF EXISTS `categories_of_news`;
CREATE TABLE `categories_of_news` (
  `category` int(10) unsigned NOT NULL COMMENT 'Category of news.',
  `news` int(10) unsigned NOT NULL COMMENT 'News of category.',
  `sort_order` int(10) unsigned NOT NULL DEFAULT '1' COMMENT 'Custom sort order.',
  `date_added` datetime NOT NULL COMMENT 'Date when category is associated with the news.',
  UNIQUE KEY `idxUCategoriesOfNews` (`category`,`news`) USING BTREE,
  KEY `idxFNewsOfCategory` (`news`) USING BTREE,
  KEY `idxFCategoryOfNews` (`category`) USING BTREE,
  KEY `idxNCategoriesoFNewsDateAdded` (`date_added`) USING BTREE,
  CONSTRAINT `idxFNewsOfCategory` FOREIGN KEY (`news`) REFERENCES `news` (`id`),
  CONSTRAINT `idxFCategoryOfNews` FOREIGN KEY (`category`) REFERENCES `news_categoy` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci ROW_FORMAT=COMPACT;

-- ----------------------------
-- Table structure for files_of_news
-- ----------------------------
DROP TABLE IF EXISTS `files_of_news`;
CREATE TABLE `files_of_news` (
  `news` int(10) unsigned NOT NULL COMMENT 'News of file.',
  `file` int(10) unsigned NOT NULL COMMENT 'File of news.',
  `language` int(5) unsigned NOT NULL COMMENT 'Language of File.',
  `date_added` datetime NOT NULL COMMENT 'Date when file is added to the news.',
  `sort_order` int(10) NOT NULL DEFAULT '1' COMMENT 'Custom sort order.',
  UNIQUE KEY `idxUFilesOfNews` (`news`,`file`) USING BTREE,
  KEY `idxFFilesOfNews` (`news`) USING BTREE,
  KEY `idxFNewsOfFile` (`file`) USING BTREE,
  KEY `idxFLanguageOfFileOfNews` (`language`) USING BTREE,
  CONSTRAINT `idxFFilesOfNews` FOREIGN KEY (`news`) REFERENCES `news` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `idxFLanguageOfFileOfNews` FOREIGN KEY (`language`) REFERENCES `language` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `idxFNewsOfFile` FOREIGN KEY (`file`) REFERENCES `file` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci ROW_FORMAT=COMPACT;

-- ----------------------------
-- Table structure for news
-- ----------------------------
DROP TABLE IF EXISTS `news`;
CREATE TABLE `news` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'System given id.',
  `date_added` datetime NOT NULL,
  `date_published` datetime NOT NULL COMMENT 'Date when the news will be published.',
  `date_unpublished` datetime DEFAULT NULL COMMENT 'Date when the nes will be unpublished.',
  `status` char(1) COLLATE utf8_turkish_ci NOT NULL DEFAULT 'p' COMMENT 'p: published, u:unpublished, m:moderation',
  `url` text COLLATE utf8_turkish_ci COMMENT 'URL of news.',
  `sort_order` int(10) unsigned NOT NULL DEFAULT '1' COMMENT 'Custom sort order.',
  `site` int(10) unsigned DEFAULT NULL COMMENT 'Site that news belong to.',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idxUNewsId` (`id`) USING BTREE,
  KEY `idxNNewsDateAdded` (`date_added`) USING BTREE,
  KEY `idxNNewsDatePublished` (`date_published`) USING BTREE,
  KEY `idxNNewsDateUnpublished` (`date_unpublished`) USING BTREE,
  KEY `idxFSiteOfNews` (`site`) USING BTREE,
  CONSTRAINT `idxFSiteOfNews` FOREIGN KEY (`site`) REFERENCES `site` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci ROW_FORMAT=COMPACT;

-- ----------------------------
-- Table structure for news_category_localization
-- ----------------------------
DROP TABLE IF EXISTS `news_category_localization`;
CREATE TABLE `news_category_localization` (
  `category` int(10) unsigned NOT NULL COMMENT 'Localized news category.',
  `language` int(5) unsigned NOT NULL COMMENT 'Language of localization.',
  `name` varchar(45) COLLATE utf8_turkish_ci NOT NULL COMMENT 'Localized name.',
  `url_key` varchar(155) COLLATE utf8_turkish_ci NOT NULL COMMENT 'Localized url key.',
  UNIQUE KEY `idxUNewsCategoryLocalization` (`language`,`category`) USING BTREE,
  UNIQUE KEY `idxUNewsCategoryUrlKey` (`url_key`,`language`,`category`) USING BTREE,
  KEY `idxFLocalizedNewsCategory` (`category`) USING BTREE,
  KEY `idxFNewsCategoryLocalizationLanguage` (`language`) USING BTREE,
  CONSTRAINT `idxFNewsCategoryLocalizationLanguage` FOREIGN KEY (`language`) REFERENCES `language` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `idxFLocalizedNewsCategory` FOREIGN KEY (`category`) REFERENCES `news_categoy` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci ROW_FORMAT=COMPACT;

-- ----------------------------
-- Table structure for news_categoy
-- ----------------------------
DROP TABLE IF EXISTS `news_categoy`;
CREATE TABLE `news_categoy` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'System given id.',
  `site` int(10) unsigned DEFAULT NULL COMMENT 'Site that news category belongs to.',
  `date_added` datetime NOT NULL COMMENT 'Date when the entry is first added.',
  `date_updated` datetime NOT NULL COMMENT 'Date when the entry is last updated.',
  `date_removed` datetime DEFAULT NULL COMMENT 'Date when the entry is marked as removed.',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idxUNewsCategoryId` (`id`) USING BTREE,
  KEY `idxFSiteOfNewsCategory` (`site`) USING BTREE,
  KEY `idxNNewsCategoryDateAdded` (`date_added`),
  KEY `idxNNewsCategoryDateUpdated` (`date_updated`),
  KEY `idxNNewsCategoryDateRemoed` (`date_removed`),
  CONSTRAINT `idxFSiteOfNewsCategory` FOREIGN KEY (`site`) REFERENCES `site` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci ROW_FORMAT=COMPACT;

-- ----------------------------
-- Table structure for news_localization
-- ----------------------------
DROP TABLE IF EXISTS `news_localization`;
CREATE TABLE `news_localization` (
  `news` int(10) unsigned NOT NULL COMMENT 'Localized news.',
  `language` int(10) unsigned NOT NULL COMMENT 'Localization language.',
  `title` varchar(155) COLLATE utf8_turkish_ci DEFAULT NULL COMMENT 'Localized title.',
  `url_key` varchar(255) COLLATE utf8_turkish_ci DEFAULT NULL COMMENT 'Localized url key.',
  `summary` varchar(255) COLLATE utf8_turkish_ci DEFAULT NULL COMMENT 'Localized summary.',
  `content` text COLLATE utf8_turkish_ci COMMENT 'Localized content.',
  UNIQUE KEY `idxUNewsLocalization` (`news`,`language`) USING BTREE,
  UNIQUE KEY `idxUNewsUrlKey` (`news`,`language`,`url_key`) USING BTREE,
  KEY `idxFLocalizedNews` (`news`) USING BTREE,
  KEY `idxFNewsLocalizationLanguage` (`language`) USING BTREE,
  CONSTRAINT `idxFLocalizedNews` FOREIGN KEY (`news`) REFERENCES `news` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `idxFNewsLocalizationLanguage` FOREIGN KEY (`language`) REFERENCES `language` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci ROW_FORMAT=COMPACT;
