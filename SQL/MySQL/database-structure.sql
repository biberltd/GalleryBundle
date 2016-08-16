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
  UNIQUE KEY `idxUGalleryId` (`id`) USING BTREE,
  KEY `idxNGalleryDatePublished` (`date_published`) USING BTREE,
  KEY `idxNGalleryDateAdded` (`date_added`) USING BTREE,
  KEY `idxNGalleryDateUpdated` (`date_updated`) USING BTREE,
  KEY `idxNGalleryDateUnpublished` (`date_unpublished`) USING BTREE,
  KEY `idxFGallerySite` (`site`) USING BTREE,
  KEY `idxFPreviewFile` (`preview_file`) USING BTREE,
  KEY `idxFGalleryFolder` (`folder`),
  CONSTRAINT `idxFFolderOfGallery` FOREIGN KEY (`folder`) REFERENCES `file_upload_folder` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `idxFSiteOfGallery` FOREIGN KEY (`site`) REFERENCES `site` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `idxFPreviewFileOfGallery` FOREIGN KEY (`preview_file`) REFERENCES `file` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
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
  UNIQUE KEY `idxUGalleryCategoryId` (`id`),
  KEY `idxNGalleryCategoryDateAdded` (`date_added`),
  KEY `idxNGalleryCategoryDateUpdated` (`date_updated`),
  KEY `idxNGalleryCategoryDateRemoved` (`date_removed`),
  KEY `idxFGalleryCategoryParent` (`parent`),
  CONSTRAINT `idxFParentOfGalleryCategory` FOREIGN KEY (`parent`) REFERENCES `gallery_category` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
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
  UNIQUE KEY `idxUGalleryCategoryLocalization` (`category`,`language`),
  UNIQUE KEY `idxUGalleryCategoryLocalizationUrlKey` (`category`,`language`,`url_key`),
  KEY `idxFGalleryCategoryLocalizationLanguage` (`language`),
  CONSTRAINT `idxFGalleryOfGalleryCategoryLocalization` FOREIGN KEY (`category`) REFERENCES `gallery_category` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `idxFLanguageOfGalleryCategoryLocalization` FOREIGN KEY (`language`) REFERENCES `language` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
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
  UNIQUE KEY `idxUGalleryLocalization` (`language`,`gallery`) USING BTREE,
  UNIQUE KEY `idxUGalleryLocalizationUrlKey` (`language`,`url_key`) USING BTREE,
  CONSTRAINT `idxFGalleryOfGalleryLocalization` FOREIGN KEY (`gallery`) REFERENCES `gallery` (`id`),
  CONSTRAINT `idxFLanguageOfGalleryLocalization` FOREIGN KEY (`language`) REFERENCES `language` (`id`)
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
  UNIQUE KEY `idxUGalleryMedia` (`gallery`,`file`) USING BTREE,
  KEY `idxFGalleryMediaFile` (`file`) USING BTREE,
  KEY `idxNGalleryMediaDateAdded` (`date_added`) USING BTREE,
  CONSTRAINT `idxFFileOfGalleryMedia` FOREIGN KEY (`file`) REFERENCES `file` (`id`),
  CONSTRAINT `idxFGalleryOfGalleryMedia` FOREIGN KEY (`gallery`) REFERENCES `gallery` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_turkish_ci ROW_FORMAT=COMPACT;


-- ----------------------------
--  Table structure for `categories_of_gallery`
-- ----------------------------
DROP TABLE IF EXISTS `categories_of_gallery`;
CREATE TABLE `categories_of_gallery` (
  `date_added` datetime NOT NULL,
  `date_updated` datetime NOT NULL,
  `date_removed` datetime DEFAULT NULL,
  `gallery` int(10) unsigned DEFAULT NULL,
  `category` int(5) unsigned DEFAULT NULL,
  KEY `gallery` (`gallery`),
  KEY `category` (`category`),
  CONSTRAINT `idxFGalleryOfCategoriesOfGallery` FOREIGN KEY (`gallery`) REFERENCES `gallery_category` (`id`) ON DELETE CASCADE,
  CONSTRAINT `idxFCategoryOfCategoriesOfGallery` FOREIGN KEY (`category`) REFERENCES `gallery` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

SET FOREIGN_KEY_CHECKS = 1;
