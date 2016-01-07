<?Php
//Database server
$config['db_host']='localhost';
//Database user
$config['db_user']='youruser';
//Database password
$config['db_password']='yourpassword';
//Database name
$config['db_name']='rc-rank';
//Federation name used in URLs and table names, should not be too long and preferably without spaces
$config['federation']='yourfederation';
//Outdoor season start week
$config['outdoor_start_week']=15;
//Outdoor season end week
$config['outdoor_end_week']=40;
//ISO country code for valid licenses
$config['LizISOCode']='NO';
//Username for the MyRCM SOAP interface
$config['SOAP_user']='rcm_user';
//Password for the MyRCM SOAP interface
$config['SOAP_password']='rcm_password';

//These parameters are used by section_mapping.php

//Different names for the championships.
//The value should be an array with normalized championship names as keys with arrays with other names as value
$config['championship_names']=array('NC'=>array('NC','nc','Norgescup','Norges cup'),'NM'=>array('NM','Norgesmesterskap','OMK - NM'),'RB'=>array('RB'),'VM'=>array('VM'),'Nordisk','Online Event','Mjøscup','Grenland Cup','Onsdagscup','Minicupen');
//Championships to calculate ranking for
$config['counted_championships']=array('NC','Nordisk','NM');
//Words in laps that should not be counted
$config['words_not_counting']=array('Support','Rekrutt','Debutant','klubbløp');
//Championships and classes where all rounds should count
//The value should be an array with championships as keys with new arrays with classes as values
$config['count_all_rounds']=array('NM'=>array('Offroad_1:8_Fuel'));
?>