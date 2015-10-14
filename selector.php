<?Php
if(!isset($dom))
{
	require 'tools/DOMDocument_createElement_simple.php';
	$dom=new DOMDocumentCustom;
	$dom->formatOutput=true;
}

function selector($title,$objects,$target,$get_parameter,$return_dom=false)
{
	global $dom;
	$div=$dom->createElement_simple('div',false,array('id'=>$get_parameter));
	$dom->createElement_simple('h3',$div,false,$title);
	$ul=$dom->createElement_simple('ul',$div);
	foreach($objects as $key=>$value)
	{
		if(is_numeric($key))
			$key=$value;

		$arguments=http_build_query(array_merge($_GET,array($get_parameter=>$key)));
		//$return.=sprintf('<a href="%s?%s">%s</a><br />',$target,$arguments,$value)."\n";
		$li=$dom->createElement_simple('li',$ul);
		$dom->createElement_simple('a',$li,array('href'=>$target.'?'.$arguments),$value);
	}
	if($return_dom===false)
		return $dom->saveXML($div);
	else
		return $div;
}
?>