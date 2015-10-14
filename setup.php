<?php
require 'class_rc_rank.php';
$federation=$argv[1];
$rc_rank=new rc_rank;
$rc_rank->debug=true;
if($rc_rank->init($federation)===false)
	die($rc_rank->error."\n");

$rc_rank->query(sprintf('CREATE TABLE `events_%s` (
  `primaryKey` int(11) NOT NULL,
  `externalKey` varchar(45) DEFAULT NULL,
  `customerKey` varchar(45) DEFAULT NULL,
  `eventName` varchar(100) DEFAULT NULL,
  `hostName` varchar(100) DEFAULT NULL,
  `block` varchar(45) DEFAULT NULL,
  `startDate` varchar(45) DEFAULT NULL,
  `endDate` varchar(45) DEFAULT NULL,
  `eventType` varchar(45) DEFAULT NULL,
  `subDirectory` varchar(45) DEFAULT NULL,
  `openRegistrationDate` varchar(45) DEFAULT NULL,
  `closeRegistrationDate` varchar(45) DEFAULT NULL,
  `notification` varchar(45) DEFAULT NULL,
  `championship` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`primaryKey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;',$rc_rank->federation));

$rc_rank->query(sprintf("CREATE TABLE `sections_%s` (
  `primaryKey` int(11) NOT NULL,
  `externalKey` int(11) DEFAULT NULL,
  `name` varchar(45) NOT NULL,
  `code` varchar(45) DEFAULT NULL,
  `subDirectory` varchar(45) DEFAULT NULL,
  `index` int(11) DEFAULT NULL,
  `modelType` varchar(45) DEFAULT NULL,
  `modelScale` varchar(45) DEFAULT NULL,
  `juniorAge` int(3) DEFAULT NULL,
  `youthAge` int(3) DEFAULT NULL,
  `seniorAge` int(3) DEFAULT NULL,
  `eventKey` int(11) NOT NULL,
  PRIMARY KEY (`primaryKey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='MyRCM event sections';",$rc_rank->federation));

//$rc_rank->query(sprintf('',$rc_rank->federation));

$rc_rank->query(sprintf("CREATE TABLE `championships_%s` (
  `sectionKey` int(11) NOT NULL COMMENT 'Key for a section in a MyRCM event',
  `eventKey` int(11) NOT NULL COMMENT 'Key for event on MyRCM',
  `championship` varchar(45) NOT NULL,
  `year` int(4) NOT NULL,
  `round` int(11) NOT NULL,
  `class` varchar(45) NOT NULL,
  PRIMARY KEY (`sectionKey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
",$rc_rank->federation));

$rc_rank->query(sprintf('CREATE TABLE `points_%s` (
  `id` varchar(45) NOT NULL,
  `sectionKey` int(11) NOT NULL,
  `eventKey` int(11) NOT NULL,
  `Rank` int(11) NOT NULL,
  `PilotKey` int(6) NOT NULL,
  `FirstName` varchar(45) NOT NULL,
  `LastName` varchar(45) NOT NULL,
  `License` varchar(45) DEFAULT NULL,
  `LicenseAddOn` varchar(45) DEFAULT NULL,
  `LicenseISOCode` varchar(45) DEFAULT NULL,
  `Licenser` varchar(45) DEFAULT NULL,
  `AgeGroup` varchar(45) DEFAULT NULL,
  `Country` varchar(45) DEFAULT NULL,
  `Points` int(3) NOT NULL,
  `Championship` varchar(45) NOT NULL,
  `Class` varchar(45) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
',$rc_rank->federation));
$rc_rank->query(sprintf("CREATE TABLE `classes_%s` (
  `id` varchar(45) NOT NULL COMMENT 'Hierarchic class id\nFormat: [Offroad/Onroad]_[Scale]_[El/Fuel]_[Extra info (stock/mod) (2WD/4WD)]',
  `name` varchar(45) NOT NULL COMMENT 'Display friendly class name',
  `in_out` varchar(45) NOT NULL COMMENT 'Indoor or outdoor',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
",$rc_rank->federation));
$rc_rank->query(sprintf("INSERT INTO `classes_%s` VALUES ('Offroad_1:10_El_2WD','1:10 Offroad 2WD','out'),('Offroad_1:10_El_4WD','1:10 Offroad 4WD','out'),('Offroad_1:10_El_SC','1:10 Offroad Short Course','out'),('Offroad_1:8_El','1:8 Offroad Elektro','out'),('Offroad_1:8_Fuel','1:8 Offroad IC','out'),('Onroad_1:10_El_Mod','1:10 Touring Modified','out'),('Onroad_1:10_El_Mod_In','1:10 Touring Modified inne','in'),('Onroad_1:10_El_Stock','1:10 Touring Stock','out'),('Onroad_1:10_Fuel','1:10 Track IC 200mm','out'),('Onroad_1:12_El','1:12 Track','out'),('Onroad_1:12_El_In','1:12 Elektro Inne','in'),('Onroad_1:5_Fuel','1:5 Large Scale Touring','out'),('Onroad_1:6_Fuel_Formel1','1:6 Formel 1','out'),('Onroad_1:8_Fuel','1:8 Track IC','out');",$rc_rank->federation));

echo "Tables created for $federation\n";

?>