<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Untitled Document</title>
</head>

<body>
<?Php
require_once 'selector.php';

require_once 'class_rc_rank.php';

$rc_rank=new rc_rank;
if(isset($_GET['federation']))
{
	$federation=$_GET['federation'];
	$rc_rank->init($federation);
}

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
$filename=basename(__FILE__);
if(!isset($_GET['federation']))
	echo selector(_('Select federation'),$rc_rank->get_federations(),$filename,'federation');
elseif($rc_rank->init($_GET['federation'])===false)
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
	$st_select_drivers=$rc_rank->db->prepare($q=sprintf('	SELECT points_%1$s.*,CONCAT(FirstName, " ", LastName) AS name,championships_%1$s.round 
														FROM points_%1$s,championships_%1$s 
														WHERE points_%1$s.sectionKey=championships_%1$s.sectionKey
														AND points_%1$s.championship=? 
														AND points_%1$s.year=? 
														AND points_%1$s.class=? 
														ORDER BY name,round',$rc_rank->federation));

	require 'calculate_points.php';
	$rc_rank->execute($st_select_drivers,array($parameters['championship'],$parameters['year'],$parameters['class']),false);

		$st=$rc_rank->query(sprintf('DELETE FROM championship_results_%s WHERE championship=%s AND year=%s AND class=%s',$rc_rank->federation,$parameters_quoted['championship'],$parameters_quoted['year'],$parameters_quoted['class']),false);
		if($st===false)
			die($rc_rank->error);

		$st_insert_info=$rc_rank->db->prepare(sprintf('INSERT INTO championship_results_%s (FirstName,LastName,championship,year,class,points,last_round,place_last_round) VALUES (?,?,?,?,?,?,?,?)',$rc_rank->federation));
	$rowcount=$st_select_drivers->rowCount();
	for($i=1; $i<=$rowcount+1; $i++)
	{
			if($i<=$rowcount)
				$driver=$st_select_drivers->fetch(PDO::FETCH_ASSOC);
			$results[$driver['name']][$driver['round']]=$driver;
			
			//SQL sorting makes all rounds of each driver after each other. When a different driver is found, total points for the previous driver can be calculated
			if((isset($previous_name) && $driver['name']!=$previous_name) || $i>$rowcount)
			{
				/*if(count($results[$previous_name])>1)
				{
				print_r($results[$previous_name]);
				print_r(array_column($results[$previous_name],'Points'));
				break;
				}*/
				//print_r($results[$previous_name]);
				$points_driver=array_column($results[$previous_name],'Points');

				//Check if all rounds should be counted for the current championship and class
				if(!isset($rc_rank->count_all_rounds[$parameters['championship']]) || array_search($parameters['class'],$rc_rank->count_all_rounds[$parameters['championship']])===false)
				{
					if(count($points_driver)>3) //Keep only the best rounds (if more than 3 run)
					{
						sort($points_driver); //Sort the drivers points in reverse order
						unset($points_driver[0]); //Remove the worst round
					}
				}

				$last_round=end($results[$previous_name]);
				$result=$rc_rank->execute($st_insert_info,array($last_round['FirstName'],$last_round['LastName'],$last_round['Championship'],$last_round['Year'],$last_round['Class'],$points=array_sum($points_driver),$last_round['round'],$last_round['Rank']),false);
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

}
?>

</body>
</html>