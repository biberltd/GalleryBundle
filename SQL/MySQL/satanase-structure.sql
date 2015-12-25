/**
 * @author		Can Berkol
 *
 * @copyright   Biber Ltd. (http://www.biberltd.com) (C) 2015
 * @license     GPLv3
 *
 * @date        23.12.2015
 */
SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for gallery
-- ----------------------------
DROP TABLE IF EXISTS `gallery`;
CREATE TABLE `gallery` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'System given id.',
  `date_added` datetime NOT NULL COMMENT 'Date when the gallery added.',
  `date_updated` datetime NOT NULL COMMENT 'Date when the gallery is last updated.',
  `date_published` datetime NOT NULL COMMENT 'Date when the gallery will be published.',
  `date_unpublished` datetime DEFAULT NULL COMMENT 'Date when the galler is to be unpublished.',
  `count_media` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Media count.',
  `count_image` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Image count',
  `count_video` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Video count.',
  `count_audio` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Audio count.',
  `count_document` int(10) unsigned NOT NULL COMMENT 'Document count.',
  `site` int(10) unsigned DEFAULT NULL COMMENT 'Site that gallery belongs to.',
  `preview_file` int(10) unsigned DEFAULT NULL COMMENT 'Preview image.',
  `folder` int(10) unsigned DEFAULT NULL COMMENT 'Upload folder of gallery.',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_u_gallery_id` (`id`) USING BTREE,
  KEY `idx_n_gallery_date_published` (`date_published`) USING BTREE,
  KEY `idx_n_gallery_date_added` (`date_added`) USING BTREE,
  KEY `idx_n_gallery_date_updated` (`date_updated`) USING BTREE,
  KEY `idx_n_gallery_date_unpublished` (`date_unpublished`) USING BTREE,
  KEY `idx_f_gallery_site` (`site`) USING BTREE,
  KEY `idx_f_preview_file` (`preview_file`) USING BTREE,
  KEY `idx_f_gallery_folder` (`folder`),
  CONSTRAINT `idx_f_gallery_folder` FOREIGN KEY (`folder`) REFERENCES `file_upload_folder` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `idx_f_gallery_site` FOREIGN KEY (`site`) REFERENCES `site` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `idx_f_preview_file` FOREIGN KEY (`preview_file`) REFERENCES `file` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci ROW_FORMAT=COMPACT;

-- ----------------------------
-- Table structure for gallery_category
-- ----------------------------
DROP TABLE IF EXISTS `gallery_category`;
CREATE TABLE `gallery_category` (
  `id` int(5) unsigned NOT NULL AUTO_INCREMENT COMMENT 'System given id.',
  `date_added` datetime NOT NULL COMMENT 'Date when the category is first added.',
  `date_updated` datetime NOT NULL COMMENT 'Date when the gallery is last updated.',
  `date_removed` datetime DEFAULT NULL COMMENT 'Date when the gallery is removed.',
  `parent` int(5) unsigned DEFAULT NULL COMMENT 'Parent category.',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_u_gallery_Category_id` (`id`),
  KEY `idx_n_gallery_category_date_added` (`date_added`),
  KEY `idx_n_gallery_category_date_updated` (`date_updated`),
  KEY `idx_n_gallery_category_date_removed` (`date_removed`),
  KEY `idx_f_gallery_category_parent` (`parent`),
  CONSTRAINT `idx_f_gallery_category_parent` FOREIGN KEY (`parent`) REFERENCES `gallery_category` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci;

-- ----------------------------
-- Table structure for gallery_category_localization
-- ----------------------------
DROP TABLE IF EXISTS `gallery_category_localization`;
CREATE TABLE `gallery_category_localization` (
  `category` int(5) unsigned NOT NULL COMMENT 'Localized category.',
  `language` int(5) unsigned NOT NULL COMMENT 'Localization language.',
  `name` varchar(155) COLLATE utf8_turkish_ci DEFAULT NULL COMMENT 'Name of category.',
  `url_key` varchar(255) COLLATE utf8_turkish_ci DEFAULT NULL COMMENT 'Auto generated from name.',
  PRIMARY KEY (`category`,`language`),
  UNIQUE KEY `idx_u_gallery_category_localization` (`category`,`language`),
  UNIQUE KEY `idx_u_gallery_category_localization_url_key` (`category`,`language`,`url_key`),
  KEY `idx_f_gallery_category_localization_language` (`language`),
  CONSTRAINT `idx_f_gallery_category_localization_category` FOREIGN KEY (`category`) REFERENCES `gallery_category` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `idx_f_gallery_category_localization_language` FOREIGN KEY (`language`) REFERENCES `language` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci;

-- ----------------------------
-- Table structure for gallery_localization
-- ----------------------------
DROP TABLE IF EXISTS `gallery_localization`;
CREATE TABLE `gallery_localization` (
  `gallery` int(10) unsigned NOT NULL COMMENT 'Localized gallery.',
  `language` int(10) unsigned NOT NULL COMMENT 'Localization language.',
  `title` varchar(55) COLLATE utf8_turkish_ci NOT NULL COMMENT 'Localized title of gallery.',
  `url_key` varchar(155) COLLATE utf8_turkish_ci NOT NULL COMMENT 'Localized url key of the gallery.',
  `description` varchar(255) COLLATE utf8_turkish_ci NOT NULL COMMENT 'Localized description of the gallery.',
  PRIMARY KEY (`gallery`,`language`),
  UNIQUE KEY `idx_u_gallery_localization` (`language`,`gallery`) USING BTREE,
  UNIQUE KEY `idx_u_gallery_localization_url_key` (`language`,`url_key`) USING BTREE,
  CONSTRAINT `idx_f_gallery_localization_gallery` FOREIGN KEY (`gallery`) REFERENCES `gallery` (`id`),
  CONSTRAINT `idx_f_gallery_localization_language` FOREIGN KEY (`language`) REFERENCES `language` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci ROW_FORMAT=COMPACT;

-- ----------------------------
-- Table structure for gallery_media
-- ----------------------------
DROP TABLE IF EXISTS `gallery_media`;
CREATE TABLE `gallery_media` (
  `gallery` int(10) unsigned NOT NULL COMMENT 'Gallery that file belongs to.',
  `file` int(10) unsigned NOT NULL COMMENT 'File that is located in gallery.',
  `type` char(1) COLLATE utf8_turkish_ci NOT NULL COMMENT 'a:audio;v:video;e:embed;i:image',
  `sort_order` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Custom sort order.',
  `date_added` datetime NOT NULL COMMENT 'Date when the file is added to gallery.',
  `count_view` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'View count of the file within the gallery.',
  `status` char(1) COLLATE utf8_turkish_ci NOT NULL DEFAULT 'p' COMMENT 'p:published,u:unpublished,d:deleted',
  UNIQUE KEY `idx_u_gallery_media` (`gallery`,`file`) USING BTREE,
  KEY `idx_f_gallery_media_file` (`file`) USING BTREE,
  KEY `idx_n_gallery_media_date_added` (`date_added`) USING BTREE,
  CONSTRAINT `idx_f_gallery_media_file` FOREIGN KEY (`file`) REFERENCES `file` (`id`),
  CONSTRAINT `idx_f_gallery_media_gallery` FOREIGN KEY (`gallery`) REFERENCES `gallery` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci ROW_FORMAT=COMPACT;
