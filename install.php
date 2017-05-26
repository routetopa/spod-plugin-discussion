<?php

$sql = 'CREATE TABLE IF NOT EXISTS `' . OW_DB_PREFIX . 'spod_discussion_comment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entityId` int(11) NOT NULL,
  `ownerId` int(11) NOT NULL,
  `comment` text,
  `timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;'
;

OW::getDbo()->query($sql);

//$path = OW::getPluginManager()->getPlugin('spodagora')->getRootDir() . 'langs.zip';
//BOL_LanguageService::getInstance()->importPrefixFromZip($path, 'spodagora');
