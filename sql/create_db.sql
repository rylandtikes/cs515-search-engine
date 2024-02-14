CREATE TABLE `keywords` (
  `kwID` int(11) NOT NULL AUTO_INCREMENT,
  `keyword` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`kwID`)
) ENGINE=InnoDB AUTO_INCREMENT=9202 DEFAULT CHARSET=latin1;

CREATE TABLE `meta_description` (
  `descriptionID` int(11) NOT NULL AUTO_INCREMENT,
  `description` varchar(160) DEFAULT NULL,
  PRIMARY KEY (`descriptionID`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=latin1;

CREATE TABLE `meta_keywords` (
  `keywordsID` int(11) NOT NULL AUTO_INCREMENT,
  `keywords` varchar(160) DEFAULT NULL,
  PRIMARY KEY (`keywordsID`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=latin1;

CREATE TABLE `url_title` (
  `urlID` int(11) NOT NULL AUTO_INCREMENT,
  `url` varchar(128) DEFAULT NULL,
  `title` varchar(128) DEFAULT NULL,
  `keywordsID` int(11) DEFAULT NULL,
  `descriptionID` int(11) DEFAULT NULL,
  PRIMARY KEY (`urlID`)
) ENGINE=InnoDB AUTO_INCREMENT=17485 DEFAULT CHARSET=latin1;

CREATE TABLE `www_index` (
  `kwID` int(11) NOT NULL,
  `urlID` int(11) NOT NULL,
  PRIMARY KEY (`kwID`,`urlID`),
  KEY `urlID` (`urlID`),
  CONSTRAINT `www_index_ibfk_1` FOREIGN KEY (`kwID`) REFERENCES `keywords` (`kwID`),
  CONSTRAINT `www_index_ibfk_2` FOREIGN KEY (`urlID`) REFERENCES `url_title` (`urlID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
