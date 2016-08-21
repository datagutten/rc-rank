<?Php
require 'class_rc_rank.php';
$rc_rank=new rc_rank;

if(isset($_GET['federation']) && array_search($_GET['federation'],$rc_rank>federations)!==false)
{
	$_SESSION['federation']=$_GET['federation'];
	header('Location: '.$_GET['uri']);
	die();
}
require 'selector.php';


?>

<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Untitled Document</title>
</head>

<body>
<?Php
echo selector(_('Select federation'),$rc_rank->get_federations(),basename(__FILE__),'federation');
?>
</body>
</html>