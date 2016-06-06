<?Php
require 'class_rc_rank.php';
$rc_rank=new rc_rank;
if(!isset($_GET['mode']))
	die();
$rc_rank->init($_GET['federation']);
if($_GET['mode']=='year')
{
	$years=range(date('Y')-1,date('Y')+1);
	$data=array_combine($years,$years);
	$next_step='event';
	$level=1;
}
elseif($_GET['mode']=='event')
{
	$st_events=$rc_rank->db->prepare(sprintf('SELECT primaryKey,eventName FROM events_%s WHERE startDate LIKE ?',$rc_rank->federation));
	$events=$rc_rank->execute($st_events,array($_GET['param'].'%'),'key_pair');
	$data=$events;
	$next_step='section';
	$level=2;
}
elseif($_GET['mode']=='section')
{
	$st_sections=$rc_rank->db->prepare(sprintf('SELECT primaryKey,name FROM sections_%s WHERE eventKey=?',$rc_rank->federation));
	$sections=$rc_rank->execute($st_sections,array($_GET['param']),'key_pair');
	$data=$sections;
	$next_step='submit';
	$level=3;
}
elseif($_GET['mode']=='championship')
{
	$championships=$rc_rank->championships($_GET['param']);
	$data=array_combine($championships,$championships);
	$next_step='class';
	$level=2;
}
elseif($_GET['mode']=='class')
{
	$classes=$rc_rank->championship_classes($_GET['param']);

	$data=$classes;

	$next_step='submit';
	$level=3;
}
if(!isset($data))
	echo json_encode(array('error'=>'No data'));
elseif($data===false)
	echo json_encode(array('error'=>$rc_rank->error));
else
	echo json_encode(array('next_step'=>$next_step,'data'=>$data,'level'=>$level));

	//echo json_encode(array('properties'=>array('federation'=>$rc_rank->federation,'next_step'=>$next_step),$data));
?>