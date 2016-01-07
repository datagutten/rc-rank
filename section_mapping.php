<!DOCTYPE HTML>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Section mapping</title>
<style type="text/css">
.event {
	font-weight:bold;
}
</style>
</head>

<body>
<?php
require 'tools/DOMDocument_createElement_simple.php';
$dom=new DOMDocumentCustom;
$dom->formatOutput=true;
require 'selector.php';

require 'class_rc_rank.php';
$rc_rank=new rc_rank;

if(isset($_GET['federation']))
{
	$federation=$_GET['federation'];
	$rc_rank->init($federation);
}
if(!isset($_GET['year']))
	$_GET['year']=date('Y');
function select($name,$options,$parent=false,$selected=false)
{
	global $dom;
	$select=$dom->createElement_simple('select',$parent,array('name'=>$name,'id'=>$name));
	foreach($options as $key=>$value)
	{
		$option=$dom->createElement_simple('option',$select,array('value'=>$key),$value);
		if($key===$selected)
			$option->setAttribute('selected','selected');
	}
	return $select;
}

$filename=basename(__FILE__);
if(!isset($_GET['federation']))
	echo selector(_('Select federation'),$rc_rank->get_federations(),$filename,'federation');
elseif(!isset($_GET['championship']))
{
	$championships=$rc_rank->counted_championships;
	if(empty($championships))
		echo _('No championships found');
	else
		echo selector(_('Select championship'),$championships,$filename,'championship');	
}
elseif(!isset($_GET['type']))
{
	echo selector(_('Select event type:'),array('OFFROAD','ONROAD'),$filename,'type');
	unset($_GET['championship']);
	echo sprintf('<p><a href="section_mapping.php?%s">'._('Select another championship').'</a></p>',http_build_query($_GET));
}
else
{
	require 'class_MyRCM.php';
	$MyRCM=new MyRCM;
	$MyRCM->soap_connect($rc_rank->federation);
	
	require 'class_MyRCM_process.php';
	$process=new MyRCM_process;
	
	$type=$_GET['type'];

	$form=$dom->createElement_simple('form',false,array('method'=>'POST'));
	$table=$dom->createElement_simple('table',$form,array('border'=>'1'));
	
	$events=$rc_rank->getEventList($type,$_GET['year']);
	//print_r($events);
	$championship=$_GET['championship'];

	if($events===false)
		echo sprintf('<span class="error">%s</span>',$rc_rank->error);
	elseif(empty($events))
		echo sprintf('<span class="warning">%s</span>',_('No events found'));
	$events_sorted=$process->sort_events($events,$rc_rank->championship_names,$_GET['year']);
	if($events_sorted===false)
		echo sprintf('<p><span class="warning">%s</span></p>',$process->error);
	elseif(!isset($events_sorted[$championship]))
		echo sprintf('<span class="warning">'._('No %s events for championship %s in %s').'</span>',strtolower($type),$championship,$rc_rank->federation);
	else
	{	
		$classes_header=array(_('Select class...'),'nocount'=>_('Not counted')); //First options in class select box
		$classes_all=$classes_header;
		
		$type_db=$rc_rank->db->quote(ucfirst(strtolower($type)).'%');
		
		$classes_out=$rc_rank->query(sprintf("SELECT id,name FROM classes_%s WHERE id LIKE %s AND in_out='out'",$rc_rank->federation,$type_db),'key_pair'); //Get outdoor classes
		$classes_in=$rc_rank->query(sprintf("SELECT id,name FROM classes_%s WHERE id LIKE %s AND in_out='in'",$rc_rank->federation,$type_db),'key_pair'); //Get indoor classes

		if(!empty($_POST)) //Form submitted
		{
			$st_insert=$rc_rank->db->prepare(sprintf('INSERT INTO championships_%s (sectionKey,eventKey,championship,year,round,class) VALUES (?,?,?,?,?,?)',$rc_rank->federation));
			//print_r($_POST);
			foreach($_POST['class'] as $eventKey=>$sections)
			{
				foreach($sections as $sectionKey=>$class)
				{
					if(empty($_POST['round_number'][$eventKey]) || $class=='0')
						continue;
					if(!is_numeric($_GET['year']))
						echo sprintf(_('Invalid year: %s'),$_GET['year']);
					$st_insert->execute(array($sectionKey,$eventKey,$championship,$_GET['year'],$_POST['round_number'][$eventKey],$class));
				}
			}
		}

		$sectionKeys_in_db=$rc_rank->query("SELECT sectionKey FROM championships_{$rc_rank->federation}",'all_column'); //Get sections in db

		foreach($events_sorted[$championship][$type] as $event)
		{
			$event=(object)$event;
			$tr_event=$dom->createElement_simple('tr',$table);
			$td=$dom->createElement_simple('td',$tr_event,array('class'=>'event'),$event->eventName);
			$dom->createElement_simple('br',$td);
			$week=date('W',strtotime($event->startDate)); //Event week
			$text=$dom->createTextNode(sprintf('%.10s-%.10s (%s: %d)',$event->startDate,$event->endDate,_('Week'),$week)); //Start and end date
			$td->appendChild($text);
			$dom->createElement_simple('br',$td);
			$text=$dom->createTextNode($event->hostName);
			$td->appendChild($text);
		
			$td=$dom->createElement_simple('td',$tr_event);
			$round_number_name=sprintf('round_number[%d]',$event->primaryKey);
			$dom->createElement_simple('label',$td,array('for'=>$round_number_name),_('Round number:'));
			$input=$dom->createElement_simple('input',$td,array('type'=>'text','size'=>'2','name'=>$round_number_name,'id'=>$round_number_name));
			if(preg_match('/'.$championship.'.{0,2}([0-9]+) /',$event->eventName,$round_number))
				$input->setAttribute('value',$round_number[1]);
		
			//preg_match('/(1:[0-9]+)/',$event['eventName'],$scale);
			if(isset($config['outdoor_start_week']))
			{
				if($week<$config['outdoor_start_week'])
					$classes=$classes_in;
				else
					$classes=$classes_out;
			}
			else
				$classes=array_merge($classes_in,$classes_out);
			$classes=array_merge($classes_header,$classes); //Prepend the header items to the class list

			$sections=$rc_rank->getSectionList($event->primaryKey);
	
			foreach($sections as $section)
			{
				$section=(object)$section;
				if(array_search($section->primaryKey,$sectionKeys_in_db)!==false) //Do not show sections already in DB
				{
					if($rc_rank->debug)
						echo sprintf(_('Section %s is already mapped'),$section->primaryKey).'<br />';
					continue;
				}
				$tr_section=$dom->createElement_simple('tr',$table);
				$td=$dom->createElement_simple('td',$tr_section,'',$section->name);
				$td=$dom->createElement_simple('td',$tr_section);
	
				$section_name_search=str_ireplace(array('OR','Fuel Track','Mod','1:10 Stock'),array('Offroad','Track IC','Modified','1:10 Touring Stock'),$section->name);
				$key=array_search($section_name_search,$classes); //Check if the section name matches a known class
		
				if($key===false && isset($rc_rank->words_not_counting)) //If the section name did not match a class, check if the section name contains "forbidden" words
				{
					foreach($rc_rank->words_not_counting as $word)
					{
						if(stripos($section->name,$word)!==false)
							$key='nocount';
					}
				}
				select(sprintf('class[%d][%d]',$event->primaryKey,$section->primaryKey),$classes,$td,$key); //Select list for class
			}
			if(!isset($tr_section))
				$table->removeChild($tr_event);
			else
				unset($tr_section);
		}
		if($table->hasChildNodes()===false)
		{
			echo _('All sections are mapped').'<br />';
			$args=$_GET;
			unset($args['type']);
			echo sprintf('<a href="section_mapping.php?%s">'._('Map another type').'</a>',http_build_query($args));
		}
		else
		{
			$dom->createElement_simple('input',$form,array('type'=>'submit','name'=>'Submit'));
			echo $dom->saveXML($form);
		}
	}
}
?>
</body>
</html>