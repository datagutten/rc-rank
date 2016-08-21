<?Php
require 'class_rc_rank.php';
$rc_rank=new rc_rank;
$init=$rc_rank->init();
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Edit points</title>
<script type="text/javascript" src="edit_points.js"></script>

</head>


<?php
require 'class_MyRCM.php';
$MyRCM=new MyRCM;
$MyRCM->init_curl();
require 'selector.php';
require_once 'tools/DOMDocument_createElement_simple.php';
$filename=basename(__FILE__);
if($init===false)
	echo $rc_rank->error;
elseif(!isset($_GET['year']) && !isset($_GET['event']))
	echo selector(_('Select year'),range(date('Y')-1,date('Y')+1),$filename,'year');
elseif(!isset($_GET['event']))
{
	$st_events=$rc_rank->db->prepare(sprintf('SELECT primaryKey,eventName FROM events_%s WHERE startDate LIKE ?',$rc_rank->federation));
	$events=$rc_rank->execute($st_events,array($_GET['year'].'%'),'key_pair');
	unset($_GET['year']); //No need for year when the events are fetched
	echo selector(_('Select event'),$events,$filename,'event');
}
elseif(!isset($_GET['section']))
{
	$st_sections=$rc_rank->db->prepare(sprintf('SELECT primaryKey,name FROM sections_%s WHERE eventKey=?',$rc_rank->federation));
	$sections=$rc_rank->execute($st_sections,array($_GET['event'].'%'),'key_pair');
	//print_r($sections);
	//die();
	unset($_GET['year']); //No need for year when the events are fetched
	echo selector(_('Select section'),$sections,$filename,'section');
}
else
{
	//$st_points=$rc_rank->db->prepare(sprintf('SELECT FirstName,LastName,License,points.PilotKey,Points,points.Rank,points.Id FROM lap_points_%1$s AS points,results_%1$s AS results, participants_%1$s WHERE points.eventKey=results.eventKey AND points.sectionKey=results.sectionKey AND points.eventKey=? AND points.PilotKey=results.PilotKey AND points.sectionKey=? ORDER BY points.rank',$rc_rank->federation));

	$st_points=$rc_rank->db->prepare(sprintf('SELECT participants.FirstName,participants.LastName,participants.LicNumber,points.PilotKey,Points,points.Rank,points.id FROM 
	lap_points_%1$s AS points,
	participants_%1$s AS participants
	WHERE points.Id=participants.id
	AND points.eventKey=?
	AND points.sectionKey=? 
	ORDER BY points.rank',$rc_rank->federation));

	$rc_rank->execute($st_points,array($_GET['event'],$_GET['section']),false);
	$body=$dom->createElement('body');
	$form=$dom->createElement_simple('form',$body,array('method'=>'post'));
	$table=$dom->createElement_simple('table',$form,array('border'=>'1'));
	
	$xml=$MyRCM->GetReport($_GET['event'],$_GET['section'],'100');
	foreach($xml->PilotList->Pilot as $Pilot)
	{
		$Pilot=(array)$Pilot->attributes();
		$Pilot=$Pilot['@attributes'];
		$all_names[$Pilot['Key']]=$Pilot['Prename'].' '.$Pilot['Name'];
	
		$participants[$Pilot['Key']]=$Pilot;
	}
	
	if($st_points->rowCount()==0)
	{
		$st_championship_info=$rc_rank->query(sprintf('SELECT * FROM championships_%s WHERE sectionKey='.$_GET['section'],$rc_rank->federation),false);
		$info=$st_championship_info->fetch(PDO::FETCH_ASSOC);

		echo sprintf('Points not calculated. <a href="calculate_points.php?federation=%1$s&year=%2$s&championship=%3$s&class=%4$s">Calculate points for %3$s %4$s %2$s</a>',$rc_rank->federation,$info['year'],$info['championship'],$info['class']);
	}
	else
	{
		while($row=$st_points->fetch(PDO::FETCH_ASSOC))
		{
			$tr=$dom->createElement_simple('tr',$table);
			if(!isset($th))
			{
				foreach(array_keys($row) as $field)
				{
					$th=$dom->createElement_simple('th',$tr,false,$field);
				}
				$tr=$dom->createElement_simple('tr',$table);
			}
			foreach($row as $key=>$value)
			{
				$td=$dom->createElement_simple('td',$tr,array('id'=>$key.'_'.$row['PilotKey']),$value);
				if($key=='Rank')
				{
					$td->nodeValue='';
					$dom->createElement_simple('input',$td,array('value'=>$value,'size'=>3,'name'=>sprintf('Rank[%s]',$row['PilotKey']),'onChange'=>'update_points('.$row['PilotKey'].',this.value)'));
					$names[$row['PilotKey']]=$row['FirstName'].' '.$row['LastName'];
				}
				elseif($key=='Points')
				{
					$dom->createElement_simple('input',$form,array('type'=>'hidden','value'=>$value,'name'=>sprintf('Points[%s]',$row['PilotKey']),'id'=>sprintf('Points_hidden_%s',$row['PilotKey'])));
				}
				
			}
			//$td=$dom->createElement_simple('td',$tr,false,$key);
			//$dom->createElement_simple('a',$td,array('href'=>''),'Zero points');
		}
		$tr=$dom->createElement_simple('tr',$table);
		$td=$dom->createElement_simple('td',$tr,array('colspan'=>'3'));
		$select=$dom->createElement_simple('select',$td,array('name'=>'driver_extra','onChange'=>'show_pilotKey(this.value)'));
		$select_names=array(''=>'--Select name--') + array_diff($all_names,$names);
		
		foreach($select_names as $key=>$name)
		{
			$option=$dom->createElement_simple('option',$select,array('value'=>$key),$name);
		}
		$td=$dom->createElement_simple('td',$tr,array('id'=>'PilotKey_extra'));
		$td=$dom->createElement_simple('td',$tr,array('id'=>'Points_extra'));
		$td=$dom->createElement_simple('td',$tr,array('id'=>'Rank_extra'));
		
		$dom->createElement_simple('input',$form,array('type'=>'hidden','name'=>sprintf('Points_extra',$row['PilotKey']),'id'=>sprintf('Points_hidden_extra',$row['PilotKey'])));
		$dom->createElement_simple('input',$td,array('size'=>3,'name'=>'Rank_extra','onChange'=>'update_points("extra",this.value)'));

		$td=$dom->createElement_simple('td',$tr,array('colspan'=>'2'));
		
		$dom->createElement_simple('button',$form,array('name'=>'submit','type'=>'submit'),'Save');
		echo $dom->saveXML($body);
		if(isset($_POST['submit']))
		{
			$st_update_rank=$rc_rank->db->prepare(sprintf('UPDATE lap_points_%s SET Rank=?,Points=? WHERE eventKey=%s AND sectionKey=%s AND PilotKey=?',$rc_rank->federation,$_GET['event'],$_GET['section']));
			foreach(array_keys($_POST['Rank']) as $driver)
			{
				$rc_rank->execute($st_update_rank,array($_POST['Rank'][$driver],$_POST['Points'][$driver],$driver));
			}
			if(is_numeric($_POST['driver_extra']) && is_numeric($_POST['Rank_extra']) && is_numeric($_POST['Points_extra']))
			{
				$participant=$participants[$_POST['driver_extra']];
				print_r($participant);
		
				//$st_event_info=$rc_rank->query(sprintf('SELECT Championship,Year,Class FROM points_%s WHERE sectionKey=%s AND eventKey=%s LIMIT 0,1',$rc_rank->federation,$_GET['section'],$_GET['event']),false);
				//$event_info=$st_event_info->fetch(PDO::FETCH_ASSOC);
				$st_insert_driver=$rc_rank->db->prepare(sprintf('INSERT INTO lap_points_%s (id,eventKey,sectionKey,PilotKey,Points,Rank) VALUES (?,?,?,?,?,?)',$rc_rank->federation));
				$id=sprintf('%s-%s-%s',$_GET['event'],$_GET['section'],$_POST['driver_extra']);
				$rc_rank->execute($st_insert_driver,$params=array($id,$_GET['event'],$_GET['section'],$_POST['driver_extra'],$_POST['Points_extra'],$_POST['Rank_extra']));
		
			}
		}
}
}
?>
</html>