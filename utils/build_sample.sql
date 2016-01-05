CREATE TABLE `minerva_index` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`thread_id` INT(11) NOT NULL DEFAULT '0',
	`post_id` INT(11) NOT NULL DEFAULT '0',
	`tag_id` INT(11) NOT NULL DEFAULT '0',
	`tag_name` VARCHAR(255) NOT NULL DEFAULT '',
	`power` MEDIUMINT(9) NOT NULL DEFAULT '0',
	`subject` VARCHAR(255) NOT NULL DEFAULT '',
	`dateline` INT(10) UNSIGNED NOT NULL DEFAULT '0',
	PRIMARY KEY (`id`),
	INDEX `thread_id` (`thread_id`),
	INDEX `post_id` (`post_id`),
	INDEX `tag_id` (`tag_id`)
)
COLLATE='gbk_chinese_ci'
ENGINE=MyISAM
AUTO_INCREMENT=0
;

CREATE TABLE `minerva_tags` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`tag_name` VARCHAR(255) NOT NULL,
	`count` MEDIUMINT(9) UNSIGNED NOT NULL DEFAULT '0',
	`type` TINYINT(4) UNSIGNED NOT NULL DEFAULT '0',
	PRIMARY KEY (`id`),
	UNIQUE INDEX `tag_name` (`tag_name`),
	INDEX `count` (`count`)
)
COLLATE='gbk_chinese_ci'
ENGINE=MyISAM
AUTO_INCREMENT=0
;