<?Php
//Calculate points for each driver in a lap and write to lap_points table
//Requires event results to be fetched in results table
require 'class_rc_rank.php';
$rc_rank=new rc_rank;
$rc_rank->debug=true;
require 'selector.php';
?>

<!DOCTYPE HTML>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Calculate points</title>
</head>

<body>
<?Php
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
	$st_get_event=$rc_rank->db->prepare(sprintf('SELECT * FROM results_%s WHERE eventKey=? AND sectionKey=?',$rc_rank->federation));
	$st_insert_points=$rc_rank->db->prepare(sprintf('INSERT IGNORE INTO lap_points_%s (id,eventKey,sectionKey,PilotKey,Points,Rank) VALUES (?,?,?,?,?,?)',$rc_rank->federation));
	echo '<h3>'.sprintf(_('Calculating points for %s %s %s'),$_GET['championship'],$_GET['class'],$_GET['year']).'</h3>';
	foreach($events as $event)
	{
		
		echo '<strong>'.sprintf('%s %s (Event: %s Section: %s)',$event['championship'],$event['round'],$event['eventKey'],$event['sectionKey'])."</strong><br />\n";
		$rc_rank->execute($st_get_event,array($event['eventKey'],$event['sectionKey']));
		if($st_get_event->rowCount()==0)
		{
			echo sprintf(_('No results for event %s'),$event['eventKey'])."<br />\n";
			continue;
		}
		while($row=$st_get_event->fetch(PDO::FETCH_ASSOC))
		{
			if($row['Rank']>count($rc_rank->EFRA_GP2))
				$points=1;
			else
				$points=$rc_rank->EFRA_GP2[$row['Rank']];
			$id=sprintf('%s-%s-%s',$event['eventKey'],$event['sectionKey'],$row['PilotKey']); //Create an unique ID
			echo sprintf(_('Calculating %s'),$id)."<br />\n";
			$rc_rank->execute($st_insert_points,array($id,$event['eventKey'],$event['sectionKey'],$row['PilotKey'],$points,$row['Rank']));
		}

	}
	echo sprintf('<a href="load_results.php?%s">%s</a>',http_build_query($_GET),_('Load results from MyRCM'))."<br />\n";
	echo sprintf('<a href="calculate_rank.php?%s">%s</a>',http_build_query($_GET),_('Calculate ranks'));
}
?>
</body>
</html>