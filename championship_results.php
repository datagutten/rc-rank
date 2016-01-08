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
	$header=$_GET['federation'].' '.$_GET['championship'].' '.$_GET['class'].' '.$_GET['year'];
	echo '<h3>'.$header.'</h3>'; //Header text
	//Get the results created by calculate_rank.php
	$st_results=$rc_rank->query(sprintf('	SELECT *,CONCAT(FirstName, " ", LastName) AS name
											FROM championship_results_%s 
											WHERE championship=%s 
											AND year=%s 
											AND class=%s 
											ORDER BY points DESC, last_round DESC, place_last_round DESC'
											,$rc_rank->federation,$parameters_quoted['championship'],$parameters_quoted['year'],$parameters_quoted['class']),false);
	if($st_results->rowCount()==0)
		echo sprintf(_('No results for %s, run %s'),$_GET['class'],sprintf('<a href="calculate_rank.php?federation=%s&championship=%s&year=%s&class=%s">%s</a>',$_GET['federation'],$_GET['championship'],$_GET['year'],$_GET['class'],'calculate_rank.php'));
	else
	{
		$rounds_info=$rc_rank->query(sprintf('SELECT * FROM championships_%s WHERE year=%s AND championship=%s AND class=%s ORDER BY round',$rc_rank->federation,$parameters_quoted['year'],$parameters_quoted['championship'],$parameters_quoted['class']),'all');
		$st_points_round=$rc_rank->db->prepare(sprintf('SELECT *,CONCAT(FirstName, " ", LastName) AS name FROM points_%s WHERE sectionKey=?',$rc_rank->federation));

		//Create header row
		$table=$dom->createElement_simple('table',false,array('border'=>'1'));
		$tr_header=$dom->createElement_simple('tr',$table);
		$dom->createElement_simple('th',$tr_header,false,_('Place'));
		$dom->createElement_simple('th',$tr_header,false,_('Name'));
		foreach($rounds_info as $round) //Write header with each round
		{
			$td=$dom->createElement_simple('th',$tr_header);
			//Link to MyRCM
			$dom->createElement_simple('a',$td,array('href'=>sprintf('http://www.myrcm.ch/myrcm/report/%s/%d/%d','en',$round['eventKey'],$round['sectionKey'])),$round['championship'].' '.$round['round']);
		}
		$dom->createElement_simple('th',$tr_header,false,_('Sum')); //Sum header text

		//Result rows
		$key=0;
		$rank=1;
		while($driver=$st_results->fetch(PDO::FETCH_ASSOC))
		{
			$tr_driver[$key]=$dom->createElement_simple('tr',$table);
			$td_rank[$key]=$dom->createElement_simple('td',$tr_driver[$key],false,$rank);
			$td_name[$key]=$dom->createElement_simple('td',$tr_driver[$key],false,$driver['name']); //Combine the drivers first and last name
			//var_dump($rank);
			foreach($rounds_info as $round)
			{
				if(!isset($points_round[$round['round']]))
				{
					$points_round[$round['round']]=$rc_rank->execute($st_points_round,array($round['sectionKey']),'all');
					$points_round[$round['round']]=array_combine(array_column($points_round[$round['round']],'name'),$points_round[$round['round']]);
				}

				if(!isset($points_round[$round['round']][$driver['name']]))
					$points='';
				else
					$points=$points_round[$round['round']][$driver['name']]['Points'];
				$dom->createElement_simple('td',$tr_driver[$key],false,$points);
			}
			$dom->createElement_simple('td',$tr_driver[$key],array('class'=>'sum'),$driver['points']); //Sum drivers points
			$rank++;
			$key++;
		}

		echo $html=$dom->saveXML($table);
		file_put_contents(str_replace(':','-',$header).'.htm',$html);
	}
	$links=$dom->createElement('ul');
	//$dom->createElement_simple('a',$links,array('href'=>sprintf('<a href="section_mapping.php?federation=%s&championship=%s&year=%s">section_mappings.php</a>',$_GET['federation'],$_GET['championship'],$_GET['year']),'section_mapping.php'));

	$url=sprintf('calculate_points.php?federation=%s&championship=%s&year=%s&class=%s',$_GET['federation'],$_GET['championship'],$_GET['year'],$_GET['class']);
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