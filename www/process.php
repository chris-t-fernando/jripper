<?
function execInBackground($cmd) { 
    if (substr(php_uname(), 0, 7) == "Windows"){ 
        pclose(popen("start /B ". $cmd, "r"));  
    } 
    else { 
        exec($cmd . " > /dev/null &");   
    } 
}


if ( (!isset($_GET["start"])) || (!isset($_GET["end"])) || (!isset($_GET["track"])) )
{
	// one or more missing parameters
	echo "missing parameter";
	die;
	
}


// if parameters are bad
if ( (!is_int(intval($_GET["start"]))) || (!is_int(intval($_GET["end"]))) )
{
	echo "start or end is not an integer";
	die;
	
}

if ( $_GET["start"] >= $_GET["end"] )
{
	echo "start after end";
	die;
	
}

// naughty input
$track = str_replace("http://j.diamonds.ro.lt/stage1/", "", urldecode($_GET["track"]));

if ( strpos($track, "/") !== false )
{
	echo "no dude";
	die;
	
}



//echo("/triplej/processor.sh " . str_replace(" ", "\\ ", escapeshellcmd($track)) . " " . $_GET["start"] . " " . $_GET["end"]);
execInBackground("/triplej/processor.sh \"" . $track . "\" " . $_GET["start"] . " " . $_GET["end"]);
$host  = $_SERVER['HTTP_HOST'];
$uri   = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
$extra = 'index.php';
header("Location: http://$host$uri/$extra");
exit;

?>
