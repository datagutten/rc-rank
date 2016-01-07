<!DOCTYPE HTML>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Calculate points</title>
</head>

<body>
<?php
//Calculate points for each event and write to DB
require 'class_rc_rank.php';

$rc_rank=new rc_rank;
$rc_rank->debug=true;


require 'class_MyRCM.php';
$MyRCM=new MyRCM;

require 'tools/DOMDocument_createElement_simple.php';
$dom=new DOMDocumentCustom;
$dom->formatOutput=true;
require 'selector.php';

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
		echo sprintf(_('No events for %s'),$_GET['class']);
	else
	{	
		echo '<h3>'.sprintf(_('Calculating points for %s %s %s'),$_GET['federation'],$_GET['championship'],$_GET['class']).'</h3>';
		//$table=$dom->createElement_simple('table',false,array('border'=>'1'));
		$st_insert=$rc_rank->db->prepare(sprintf('INSERT INTO points_%s (id,sectionKey,eventKey,Rank,PilotKey,FirstName,LastName,License,LicenseAddOn,LicenseISOCode,Licenser,AgeGroup,Country,Points,Championship,Year,Class) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',$rc_rank->federation));
		$id_indb=$rc_rank->query(sprintf('SELECT id FROM points_%s',$rc_rank->federation),'all_column');
		foreach($events as $event)
		{
			echo '<h3>'.$event['championship'].$event['round'].'</h3>';

			$ranking=$MyRCM->FinalRankingList($event['eventKey'],$event['sectionKey']);
			if($ranking===false)
			{
				echo $MyRCM->error.'<br />';
				continue;
			}
			if(!is_object($ranking))
				var_dump($ranking);
			foreach($ranking->RankingList->Ranking as $result)
			{
				$rank=$result->attributes();
				/*if($attributes->Liz=='FUN')
					$points=0;
				else*/
				$points=$rc_rank->EFRA_GP2[(int)$rank->Rank];
				echo sprintf('%d: %s %s: %d points (License: %s)',$rank->Rank,$rank->Prename,$rank->Name,$points,$rank->Liz)."<br />\n";	

				$id=sprintf('%s-%s-%s',$event['sectionKey'],$event['eventKey'],$rank->PilotKey); //Create an unique ID
				if(array_search($id,$id_indb)!==false) //Check if ID is already in DB
				{
					echo "Not updating $id, already in DB<br />\n";
					continue;
				}
				$params=array($id,$event['sectionKey'],$event['eventKey'],$rank->Rank,$rank->PilotKey,$rank->Prename,$rank->Name,$rank->Liz,$rank->AddOn,$rank->LizISOCode,$rank->LizLicenser,$rank->AgeGroup,$rank->Country,$points,$_GET['championship'],$_GET['year'],$_GET['class']);

				if($rc_rank->execute($st_insert,$params)===false)
					die($rc_rank->error);
			}
			//break;
		}
		//echo $dom->saveXML($table);
		}
}
?>
</body>
</html>