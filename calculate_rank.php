<?Php
require 'class_rc_rank.php';
$rc_rank=new rc_rank;
$init=$rc_rank->init();
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Untitled Document</title>
</head>

<body>
<?Php
foreach($_GET as $key=>$value)
{
	if(!preg_match('/[A-Za-z0-9_\-]+/',$value)) //Check if GET input contains illegal characters
		unset($_GET[$key]);
	else
	{
		$parameters_quoted[$key]=$rc_rank->db->quote($value);
		$parameters[$key]=$_GET[$key];
	}
}
if($_GET['class']=='all')
	$_GET['class']='%';

$filename=basename(__FILE__);
if($init===false)
	echo $rc_rank->error;
elseif(!isset($_GET['year']))
	echo selector(_('Select year'),range(date('Y')-1,date('Y')+1),$filename,'year');
elseif(!isset($_GET['championship']))
	echo selector(_('Select championship'),$rc_rank->championships($_GET['year']),$filename,'championship');
elseif(!isset($_GET['class']))
	echo selector(_('Select class'),$rc_rank->championship_classes($_GET['championship'],$_GET['year']),$filename,'class');
else
{
	//Get all drivers in selected championship
	/*$st_select_drivers=$rc_rank->db->prepare($q=sprintf('	SELECT points_%1$s.*,CONCAT(FirstName, " ", LastName) AS name,championships_%1$s.round 
														FROM points_%1$s_old AS points_%1$s,championships_%1$s 
														WHERE points_%1$s.sectionKey=championships_%1$s.sectionKey
														AND points_%1$s.championship=? 
														AND points_%1$s.year=? 
														AND points_%1$s.class=? 
														ORDER BY name,round',$rc_rank->federation));*/
	$st_select_drivers=$rc_rank->db->prepare($q='SELECT *,CONCAT(FirstName, " ", LastName) AS name FROM championships_NMF AS championships,
lap_points_NMF AS points,
participants_NMF AS participants
WHERE championship=? AND year=? AND class LIKE ?
AND championships.sectionKey=points.sectionKey
AND points.id=participants.id
ORDER BY name,round');									
	$number_of_rounds=$rc_rank->number_of_rounds($parameters['championship'],$parameters['year'],$_GET['class']);
	//require 'calculate_points.php';
	//Get number of rounds driven in the selected championship

	$rc_rank->execute($st_select_drivers,array($parameters['championship'],$parameters['year'],$_GET['class']));
	
	$st_delete=$rc_rank->db->prepare(sprintf('DELETE FROM championship_results_%s WHERE championship=? AND year=? AND class LIKE ?',$rc_rank->federation));
	$rc_rank->execute($st_delete,array($_GET['championship'],$_GET['year'],$_GET['class']));
	$st_insert_info=$rc_rank->db->prepare(sprintf('INSERT INTO championship_results_%s (FirstName,LastName,championship,year,class,points,last_round,place_last_round) VALUES (?,?,?,?,?,?,?,?)',$rc_rank->federation));
	$rowcount=$st_select_drivers->rowCount();
	if($rowcount==0)
	{
		echo sprintf('%s<a href="calculate_points.php?%s">%s</a>',_('No points found, '),http_build_query($_GET),_('Calculate points'));
	}
	else
	{
		echo '<h3>'._('Total points').'</h3>';
		for($i=1; $i<=$rowcount+1; $i++)
		{
				if($i<=$rowcount)
					$driver=$st_select_drivers->fetch(PDO::FETCH_ASSOC);
				$results[$driver['name']][$driver['round']]=$driver;
				
				//SQL sorting makes all rounds of each driver after each other. When a different driver is found, total points for the previous driver can be calculated
				if((isset($previous_name) && $driver['name']!=$previous_name) || $i>$rowcount)
				{
					$points_driver=array_column($results[$previous_name],'Points');
	
					//Check if all rounds should be counted for the current championship and class
					if(!isset($rc_rank->count_all_rounds[$parameters['championship']]) || array_search($parameters['class'],$rc_rank->count_all_rounds[$parameters['championship']])===false)
					{
						//Keep only the best rounds if the driver has been in all rounds of a championship
						if($number_of_rounds>3 && count($points_driver)==$number_of_rounds)
						{
							sort($points_driver); //Sort the drivers points in reverse order
							unset($points_driver[0]); //Remove the worst round
						}
					}
	
					$last_round=end($results[$previous_name]);
					$result=$rc_rank->execute($st_insert_info,array($last_round['FirstName'],$last_round['LastName'],$last_round['championship'],$last_round['year'],$last_round['class'],$points=array_sum($points_driver),$last_round['round'],$last_round['Rank']),false);
					if($result===false)
					{
						echo $rc_rank->error."<br />\n";
						break;
					}
					else
						echo sprintf("%s %s: %s %s<br />\n",$last_round['FirstName'],$last_round['LastName'],$points,ngettext('point','points',$points));
					
				}
				$previous_name=$driver['name'];
		}
		echo sprintf('<a href="championship_results.php?%s">Show results</a>',http_build_query($_GET));
		}
}
?>

</body>
</html>