<!DOCTYPE HTML>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Load events</title>
</head>

<body>
<?Php
require 'class_rc_rank.php';
$rc_rank=new rc_rank;
require 'selector.php';

echo '<h3>'._('Load events from MyRCM').'</h3>';

$filename=basename(__FILE__);

if(!isset($_GET['federation']))
	echo selector(_('Select federation'),$rc_rank->get_federations(),$filename,'federation');
elseif(!isset($_GET['year']))
{
	echo _('Enter year to load events from');
	$form=$dom->createElement_simple('form',false,array('method'=>'get'));
	$dom->createElement_simple('input',$form,array('type'=>'hidden','name'=>'federation','value'=>$_GET['federation']));
	$dom->createElement_simple('input',$form,array('type'=>'text','name'=>'year','value'=>date('Y')));

	$dom->createElement_simple('input',$form,array('type'=>'submit'));
	echo $dom->saveXML($form);
}
else
{	
	$rc_rank->init($_GET['federation']);
	
	require 'class_MyRCM.php';
	$MyRCM=new MyRCM;
	$MyRCM->soap_connect($rc_rank->federation);
	
	require 'class_MyRCM_process.php';
	$process=new MyRCM_process;

	$events=$MyRCM->getEventList('ALL');

	if($events===false)
		echo $MyRCM->error;
	else
	{
		$events_raw=$events->eventListDto->EventDto;
		$events_sorted=$process->sort_events($events_raw,$rc_rank->championship_names,$_GET['year']);
		
		
		$event_primaryKey_indb=$rc_rank->query(sprintf('SELECT primaryKey FROM events_%s',$rc_rank->federation),'all_column');
		$section_primaryKey_indb=$rc_rank->query(sprintf('SELECT primaryKey FROM sections_%s',$rc_rank->federation),'all_column');
		
		
		$st_insert_event=$rc_rank->db->prepare(sprintf('INSERT INTO events_%s (primaryKey,externalKey,customerKey,eventName,hostName,block,startDate,endDate,eventType,subDirectory,openRegistrationDate,closeRegistrationDate,notification,championship) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)',$rc_rank->federation));
		
		$st_insert_section=$rc_rank->db->prepare(sprintf('INSERT INTO sections_%s (`primaryKey`,
		`externalKey`,
		`name`,
		`code`,
		`subDirectory`,
		`index`,
		`modelType`,
		`modelScale`,
		`juniorAge`,
		`youthAge`,
		`seniorAge`,
		`eventKey`) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',$rc_rank->federation));
		//print_r($events_sorted);
		foreach($events_sorted as $championship=>$types)
		{
			if(array_search($championship,$rc_rank->counted_championships)===false)
			{
				echo "Skip\n";
				foreach($types as $type=>$events)
				{
					echo "Type: $type<br />\n";
					foreach($events as $event)
					{
						if($event['eventName']=='Online Event')
							continue;
						echo $event['eventName'].'/'.$event['hostName'].' '.$event['primaryKey']."<br />\n";
					}
				}
				continue;
			}
			foreach($types as $type=>$events)
			{
				echo "<strong>$championship $type</strong><br >\n";
				foreach($events as $event)
				{
					if(array_search($event['primaryKey'],$event_primaryKey_indb)===false) //Skip existing
					{
						$params=array($event['primaryKey'],$event['externalKey'],$event['customerKey'],$event['eventName'],$event['hostName'],$event['block'],$event['startDate'],$event['endDate'],$event['eventType'],$event['subDirectory'],$event['openRegistrationDate'],$event['closeRegistrationDate'],$event['notification'],$championship);
						$rc_rank->execute($st_insert_event,$params);
						echo sprintf(_('Event %s (%s) was inserted into table events_%s'),$event['eventName'],$event['primaryKey'],$rc_rank->federation)."<br />\n";
					}
					else
						echo sprintf(_('Event %s (%s) exists in table events_%s'),$event['eventName'],$event['primaryKey'],$rc_rank->federation)."<br />\n";
					
					$sections=$MyRCM->getSectionList($event['primaryKey']);
					foreach($sections as $section) //Write sections to DB
					{
						if(array_search($section->primaryKey,$section_primaryKey_indb)!==false) //Skip existing
							continue;
						$params=array($section->primaryKey,$section->externalKey,$section->name,$section->code,$section->subDirectory,$section->index,$section->modelType,$section->modelScale,$section->juniorAge,$section->youthAge,$section->seniorAge,$event['primaryKey']);
						$rc_rank->execute($st_insert_section,$params);
					}
					
				}
			}
		}
		echo '<a href="section_mapping.php?federation='.$rc_rank->federation.'&year='.$_GET['year'].'">'._('Events loaded. Now you can connect the events and sections to their correct classes.').'</a>';
	}
}
?>
</body>
</html>