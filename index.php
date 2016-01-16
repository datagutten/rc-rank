<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>RC-rank</title>
</head>


<?Php
require 'class_rc_rank.php';
$rc_rank=new rc_rank;

require 'tools/DOMDocument_createElement_simple.php';
$dom=new DOMDocumentCustom;
$dom->formatOutput=true;
require 'selector.php';
$body=$dom->createElement_simple('body');

$filename=basename(__FILE__);

if(!isset($_GET['federation']))
	$body->appendChild(selector(_('Select federation'),$rc_rank->get_federations(),$filename,'federation',true));
else
{
	$dom->createElement_simple('h2',$body,false,_('This is a tool to calculate points and ranks for RC championships'));
	$dom->createElement_simple('p',$body,false,_('Run these scripts in the order listed to get and process the data to get correct and complete ranking lists'));
	
	$scripts=array('load_events.php'=>_('Load events from MyRCM'),'section_mapping.php'=>_('Connect MyRCM events and sections to their correct classes'),'calculate_rank.php'=>_('Calculate points'));
	$list=$dom->createElement_simple('ol',$body);
	foreach($scripts as $url=>$text)
	{
		$li=$dom->createElement_simple('li',$list);
		$dom->createElement_simple('a',$li,array('href'=>$url.'?federation='.$_GET['federation']),$text);
	}
	$p=$dom->createElement_simple('p',$body,false,_('When the above tasks are done you can '));
	$dom->createElement_simple('a',$p,array('href'=>'championship_results.php'.'?federation='.$_GET['federation']),_('show championship results.'));
}
echo $dom->saveXML($body);
?>

</html>