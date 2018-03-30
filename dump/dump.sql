CREATE TABLE paths (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `lat` double DEFAULT NULL,
  `long` double DEFAULT NULL,
  `token` varchar(225) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=latin1;
