CREATE TABLE /*_*/`bookdesigner_outlines` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `user_id` int(10) unsigned NOT NULL,
  `shared` tinyint NOT NULL,
  `savedate` datetime NOT NULL,
  `bookname` tinytext NOT NULL,
  `outline` mediumtext NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
