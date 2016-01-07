<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Championship results</title>
</head>

<body>
<?Php
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

foreach($_GET as $key=>$value)
{
	if(!preg_match('/[A-Za-z0-9_\-]+/',$value)) //Check if GET input contains illegal characters
		unset($_GET[$key]);
	else
		$parameters_quoted[$key]=$rc_rank->db->quote($value);
}
$filename=basename(__FILE__);
if(!isset($_GET['federation']))
	echo selector(_('Select federation'),$rc_rank->get_federations(),$filename,'federation');
elseif(!isset($_GET['year']))
	echo selector(_('Select year'),range(date('Y')-1,date('Y')+1),$filename,'year');
elseif(!isset($_GET['championship']))
{
	$championships=$rc_rank->championships($_GET['year']);
	if(empty($championships))
		echo sprintf(_('No championships found, run %s'),sprintf('<a href="section_mapping.php?federation=%s">section_mapping.php</a>',$rc_rank->federation));
	else
		echo selector(_('Select championship'),$championships,$filename,'championship');
}
elseif(!isset($_GET['class']))
{
	//Get classes run in current championship current year
	$classes=$rc_rank->championship_classes($_GET['championship'],$_GET['year']);
	echo selector(_('Select class'),$classes,'championship_results.php','class');
}
else
{
	echo '<h3>'.$_GET['federation'].' '.$_GET['championship'].' '.$_GET['class'].' '.$_GET['year'].'</h3>'; //Header text

	//echo sprintf('<h3><a href="http://www.myrcm.ch/myrcm/report/%s/%d/%d">%s</a></h3>','en',);

	$drivers=$rc_rank->query(sprintf('SELECT FirstName,LastName FROM points_%s WHERE championship=%s AND class=%s GROUP BY FirstName,LastName',$federation,$parameters_quoted['championship'],$parameters_quoted['class']),'all'); //Get all drivers in selected championship
	if(empty($drivers))
		echo sprintf(_('No results for %s, run %s'),$_GET['class'],sprintf('<a href="calculate_points.php?federation=%s&championship=%s&class=%s">%s</a>',$_GET['federation'],$_GET['championship'],$_GET['class'],'calculate_points.php'));
	else
	{
		$rounds_info=$rc_rank->query($q=sprintf('SELECT * FROM championships_%s WHERE year=%s AND championship=%s AND class=%s ORDER BY round',$federation,$parameters_quoted['year'],$parameters_quoted['championship'],$parameters_quoted['class']),'all');
		$rounds=array_pop(array_column($rounds_info,'round')); //Get last round number (total number of rounds)
		//Create header row
		$table=$dom->createElement_simple('table',false,array('border'=>'1'));
		$tr_header=$dom->createElement_simple('tr',$table);
		$dom->createElement_simple('th',$tr_header,false,_('Place'));
		$dom->createElement_simple('th',$tr_header,false,_('Name'));
		foreach($rounds_info as $round) //Write header with each round
		{
			$td=$dom->createElement_simple('th',$tr_header);
			$dom->createElement_simple('a',$td,array('href'=>sprintf('http://www.myrcm.ch/myrcm/report/%s/%d/%d','en',$round['eventKey'],$round['sectionKey'])),$round['championship'].' '.$round['round']);
		}
		$dom->createElement_simple('th',$tr_header,false,_('Sum')); //Sum header text

		//Result rows
		foreach($drivers as $key=>$driver)
		{
			$q=sprintf('SELECT round,Points FROM points_%1$s,championships_%1$s
													WHERE points_%1$s.sectionKey=championships_%1$s.sectionKey 
													AND championships_%1$s.championship=%2$s 
													AND year=%3$s AND FirstName=%4$s 
													AND LastName=%5$s 
													AND points_%1$s.class=%6$s
													ORDER BY points ASC',$federation,$parameters_quoted['championship'],$parameters_quoted['year'],$rc_rank->db->quote($driver['FirstName']),$rc_rank->db->quote($driver['LastName']),$parameters_quoted['class']);

			$points_driver=$rc_rank->query($q,'key_pair');

			$tr_driver[$key]=$dom->createElement('tr');
			$td_rank[$key]=$dom->createElement_simple('td',$tr_driver[$key]);
			$td_name=$dom->createElement_simple('td',$tr_driver[$key],false,implode(' ',$driver));
			foreach($rounds_info as $round)
			{
				if(!isset($points_driver[$round['round']]))
					$points='';
				else
					$points=$points_driver[$round['round']];
				$dom->createElement_simple('td',$tr_driver[$key],false,$points);
			}
			sort($points_driver);
			//print_r($points_driver);
			
			//Check if all rounds should be counted for the current championship and class
			if(!isset($rc_rank->count_all_rounds[$_GET['championship']]) || array_search($_GET['class'],$rc_rank->count_all_rounds[$_GET['championship']])===false)
			{
				if(count($points_driver)>3/* ----Add exception for 1:8 off road ----*/ ) //Keep only the best rounds (if more than 3 run)
					unset($points_driver[0]);
			}
			$dom->createElement_simple('td',$tr_driver[$key],array('class'=>'sum'),$sum=array_sum($points_driver)); //Sum drivers points
			$scores[$key]=$sum;
		}
		arsort($scores);
	
		//print_r($scores);
		$rank=1;
		foreach($scores as $key=>$score)
		{
			$td_rank[$key]->textContent=$rank;
			$table->appendChild($tr_driver[$key]);
			$rank++;
		}
		
		echo $dom->saveXML($table);
	}
	$links=$dom->createElement('ul');
	//$dom->createElement_simple('a',$links,array('href'=>sprintf('<a href="section_mapping.php?federation=%s&championship=%s&year=%s">section_mappings.php</a>',$_GET['federation'],$_GET['championship'],$_GET['year']),'section_mapping.php'));

	$url=sprintf('calculate_points.php?federation=%s&championship=%s&class=%s',$_GET['federation'],$_GET['championship'],$_GET['class']);
	$li=$dom->createElement_simple('li',$links,false,_('Missing results? '));
	$dom->createElement_simple('a',$li,array('href'=>$url),_('Calculate points'));
	$url=sprintf('section_mapping.php?federation=%s&championship=%s&year=%s',$_GET['federation'],$_GET['championship'],$_GET['year']);
	$li=$dom->createElement_simple('li',$links,false,_('Missing events? '));
	$dom->createElement_simple('a',$li,array('href'=>$url),_('Map events'));
	
	echo $dom->saveXML($links);

}
?>
</body>
</html>