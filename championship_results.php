<?Php
require 'tools/DOMDocument_createElement_simple.php';
$dom=new DOMDocumentCustom;
$dom->formatOutput=true;

require 'class_rc_rank.php';
$rc_rank=new rc_rank;
$rc_rank->init();
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Championship results</title>
<script src="selector.js"></script>
</head>

<body>
<?Php
$form=$dom->createElement_simple('form',false,array('method'=>'get'));
$dom->createElement_simple('script',$form,false,"add_selector('year_championship','','championship','year')");
echo $dom->saveXML($form);

foreach($_GET as $key=>$value)
{
	if(!preg_match('/[A-Za-z0-9_\-]+/',$value)) //Check if GET input contains illegal characters
		unset($_GET[$key]);
	else
		$parameters_quoted[$key]=$rc_rank->db->quote($value);
}

if(isset($_GET['year']) && isset($_GET['championship']) && isset($_GET['class']))
{
	$st_class_name=$rc_rank->db->prepare(sprintf('SELECT name FROM classes_%s WHERE id=?',$rc_rank->federation));
	$class_name=$rc_rank->execute($st_class_name,array($_GET['class']),'single');
	$header=$rc_rank->federation.' '.$_GET['championship'].' '.$class_name.' '.$_GET['year'];
	echo '<h3>'.$header.'</h3>'; //Header text
	
	//Get the results created by calculate_rank.php
	$st_results=$rc_rank->query(sprintf('	SELECT *,CONCAT(FirstName, " ", LastName) AS name
											FROM championship_results_%s 
											WHERE championship=%s 
											AND year=%s 
											AND class=%s 
											ORDER BY points DESC, last_round DESC, place_last_round DESC'
											,$rc_rank->federation,$parameters_quoted['championship'],$parameters_quoted['year'],$parameters_quoted['class']),false);
	if($st_results->rowCount()>0)
	{
		$rounds_info=$rc_rank->query(sprintf('SELECT * FROM championships_%s WHERE year=%s AND championship=%s AND class=%s ORDER BY round',$rc_rank->federation,$parameters_quoted['year'],$parameters_quoted['championship'],$parameters_quoted['class']),'all');
		$st_points_round=$rc_rank->db->prepare(sprintf('SELECT *,CONCAT(FirstName, " ", LastName) AS name FROM lap_points_%1$s AS points,participants_%1$s AS participants WHERE points.id=participants.id AND points.sectionKey=?',$rc_rank->federation));

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
		while($driver=$st_results->fetch(PDO::FETCH_ASSOC)) //Fetch total results
		{
			$tr_driver[$key]=$dom->createElement_simple('tr',$table);
			$td_rank[$key]=$dom->createElement_simple('td',$tr_driver[$key],false,$rank); //Column with the rank/total place
			$td_name[$key]=$dom->createElement_simple('td',$tr_driver[$key],false,$driver['name']); //Column with the drivers name

			foreach($rounds_info as $round) //Loop through the championship rounds
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
		file_put_contents('rankinglists/'.str_replace(':','-',$header).'.htm',$html);
	}
	$links=$dom->createElement('ul');

	$url=sprintf('calculate_rank.php?championship=%s&year=%s&class=%s',$_GET['championship'],$_GET['year'],$_GET['class']);
	$li=$dom->createElement_simple('li',$links,false,_('Missing results? '));
	$dom->createElement_simple('a',$li,array('href'=>$url),_('Calculate ranks'));
	$url=sprintf('section_mapping.php?championship=%s&year=%s&type=%s',$_GET['championship'],$_GET['year'],strtoupper(substr($_GET['class'],0,strpos($_GET['class'],'_'))));
	$li=$dom->createElement_simple('li',$links,false,_('Missing events? '));
	$dom->createElement_simple('a',$li,array('href'=>$url),_('Map events'));
	
	echo $dom->saveXML($links);

}
?>
</body>
</html>