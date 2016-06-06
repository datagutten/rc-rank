<?Php
//Get results from MyRCM and write to DB without modification
if(!isset($rc_rank)) //Script is called directly and is not included
{
	?>
<!DOCTYPE HTML>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Load data</title>
</head>

<body>
	<?php 
    
    require 'class_rc_rank.php';
    $rc_rank=new rc_rank;
    $rc_rank->debug=true;
    require 'selector.php';
}
require 'class_MyRCM.php';
$MyRCM=new MyRCM;

$options=getopt('',array('championship:','class:'));
$filename=basename(__FILE__);

if(!isset($_GET['federation']))
	echo selector(_('Select federation'),$rc_rank->get_federations(),$filename,'federation');
elseif($rc_rank->init($_GET['federation'])===false)
	echo $rc_rank->error;
elseif(!isset($_GET['year']))
	echo selector(_('Select year'),range(date('Y')-1,date('Y')+1),$filename,'year');
elseif(!isset($_GET['championship']))
	echo selector(_('Select championship'),$rc_rank->championships(),$filename,'championship');
elseif(!isset($_GET['class']))
	echo selector(_('Select class'),$rc_rank->championship_classes($_GET['championship']),$filename,'class');
else
{
	$events=$rc_rank->championship_events($_GET['championship'],$_GET['year'],$_GET['class']);
	if($events===false)
		echo $rc_rank->error;
	elseif(empty($events))
	{
		echo sprintf(_('No events for %s'),$_GET['class'])."<br />";
		echo sprintf('<p><a href="section_mapping.php?%s">%s</a></p>',http_build_query(array('federation'=>$rc_rank->federation,'championship'=>$_GET['championship'],'year'=>$_GET['year'],'type'=>strtoupper(substr($_GET['class'],0,strpos($_GET['class'],'_'))))),_('Map events'));
 
	}
	else
	{
		echo '<h3>'.sprintf(_('Fetching results for %s %s %s'),$_GET['federation'],$_GET['championship'],$_GET['class']).'</h3>';
		//$table=$dom->createElement_simple('table',false,array('border'=>'1'));
		if(!isset($_GET['reload']))
			echo sprintf('<a href="?%s">%s</a>',http_build_query(array_merge($_GET,array('reload'=>'true'))),_('Remove existing points and reload data from MyRCM'));
		$st_insert=$rc_rank->db->prepare(sprintf('INSERT INTO results_%s (id,sectionKey,eventKey,Rank,PilotKey,FirstName,LastName,License,LicenseAddOn,LicenseISOCode,Licenser,AgeGroup,Country,HeatName,HeatCondition) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',$rc_rank->federation));
		$st_insert_participant=$rc_rank->db->prepare(sprintf('INSERT IGNORE INTO participants_%s (`Id`,`eventKey`,`sectionKey`,`PilotKey`,`FirstName`,`LastName`,`Country`,`Club`,`Team`,`LicNumber`,`LicAddOn`,`LicCountryCode`,`Licenser`,`AgeGroup`) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)',$rc_rank->federation));
		$id_indb=$rc_rank->query(sprintf('SELECT id FROM results_%s',$rc_rank->federation),'all_column');
		foreach($events as $event)
		{
			echo '<h3>'.$event['championship'].$event['round'].'</h3>';

			$ranking=$MyRCM->FinalRankingList($event['eventKey'],$event['sectionKey']); //Get final ranking list from MyRCM
			if($ranking===false) //Error occured
			{
				echo $MyRCM->error.'<br />';
				continue;
			}
			if(isset($_GET['reload'])) //Delete existing data from database and fetch again
			{
				$st_delete=$rc_rank->db->prepare(sprintf('DELETE FROM results_%s WHERE eventKey=? AND sectionKey=?',$rc_rank->federation));
				$rc_rank->execute($st_delete,array($event['eventKey'],$event['sectionKey']));
				echo '<p>'._('Reloading results from MyRCM').'</p>';
				$id_indb=$rc_rank->query(sprintf('SELECT id FROM results_%s',$rc_rank->federation),'all_column');
			}

			$place=1;
			foreach($ranking->RankingList->Ranking as $result) //Load results
			{
				$rank=$result->attributes();
				if(empty($rank->EndTime))
					continue;
				$points=$rc_rank->EFRA_GP2[(int)$rank->Rank];
				echo sprintf('%d: %s %s: %d points (License: %s)',$rank->Rank,$rank->Prename,$rank->Name,$points,$rank->Liz)."<br />\n";	

				$id=sprintf('%s-%s-%s',$event['eventKey'],$event['sectionKey'],$rank->PilotKey); //Create an unique ID
				if(array_search($id,$id_indb)!==false) //Check if ID is already in DB
				{
					echo "Not updating $id, already in DB<br />\n";
					continue;
				}
				$params=array($id,$event['sectionKey'],$event['eventKey'],$rank->Rank,$rank->PilotKey,$rank->Prename,$rank->Name,(string)$rank->Liz,$rank->AddOn,$rank->LizISOCode,$rank->LizLicenser,$rank->AgeGroup,$rank->Country,$rank->HeatName,$rank->HeatCondition);
				if($rc_rank->execute($st_insert,$params)===false)
					die($rc_rank->error);
				$place++;
			}
			$xml=$MyRCM->GetReport($event['eventKey'],$event['sectionKey'],'100');
			foreach($xml->PilotList->Pilot as $Pilot)
			{
				$Pilot=(array)$Pilot->attributes();
				$Pilot=$Pilot['@attributes'];
				$id=sprintf('%s-%s-%s',$event['eventKey'],$event['sectionKey'],$Pilot['Key']); //Create an unique ID
				$rc_rank->execute($st_insert_participant,array($id,$event['eventKey'],$event['sectionKey'],$Pilot['Key'],$Pilot['Prename'],$Pilot['Name'],$Pilot['Country'],$Pilot['Club'],$Pilot['Team'],$Pilot['LicNumber'],$Pilot['LicAddOn'],$Pilot['LicCountryCode'],$Pilot['Licenser'],$Pilot['AgeGroup']));
			}
			
		}
		echo sprintf('<p><a href="calculate_points.php?%s">%s</a></p>',http_build_query($_GET),_('Calculate points'));
	}
}
?>
</body>
</html>