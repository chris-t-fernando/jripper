<?

date_default_timezone_set('Australia/Victoria');
$logHandle = fopen("/tmp/record.log", "a");

fwrite($logHandle, date("Y-m-j H:i") . " - INFO - Starting check\n");

$ch = curl_init();

// set URL and other appropriate options
curl_setopt($ch, CURLOPT_URL, "http://www.abc.net.au/triplej/live/latw/");
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 

// grab URL and pass it to the browser
$result = curl_exec($ch);

// close cURL resource, and free up system resources
curl_close($ch);

// <div class="latw">
// <h4>Parkway Drive</h4>
// <h6>Live At The Wireless</h6>
// <h5>8:00pm Monday 11 January</h5>
// <p>It's horns up for the first big Live At the Wireless of the year, with PARKWAY DRIVE! Recorded by the triple j live music crew at Melbourne's Festival Hall in September last year.</p>


$lines = explode("\r\n", $result);

for ( $i=0; $i<count($lines); $i++ )
{
	if ( $lines[$i] == "<div class=\"latw\">" )
	{
		$artist = strip_tags($lines[$i+1]);
		$album = strip_tags($lines[$i+2]);
		$when = strip_tags($lines[$i+3]);
		$description = strip_tags($lines[$i+4]);
		break;
		
	}
	
}

if ( !isset($artist) )
{
	fwrite($logHandle, date("Y-m-j H:i") . " - CRIT - Failed to find latw div - ending\n***\n");
	pushover("Failed to find latw div", "Recorder failure!");
	die;

}

# year is implied, so need to work out if the month comes before the current month - if it is, its next year


if ( date_format(DateTime::createFromFormat('H:ia l j F', $when), 'm') <= date('m') )
{
       # this year
       $when .= " " . date('Y');

} else {
       # next year
       $when .= " " . intval(date('Y'))+1;

}

$date = DateTime::createFromFormat('H:ia l j F Y', $when); 

$cron = date_to_cron($date);

$cron["command"] = "/triplej/streamripper.sh \"" . $artist . "\" \"" . $description . "\"";

$newCronString = $cron["min"] . " " . $cron["hour"] . " " . $cron["day"] . " " . $cron["month"] . " * " . $cron["command"];

exec("crontab -l", $cronContents);

$found = false;

for ( $i=0; $i<count($cronContents); $i++ )
{
	if ( trim($cronContents[$i]) == "no crontab for ec2-user" )
	{
		$found = false;
		
	} else {
		if ( trim($newCronString) == ($cronContents[$i]) )
		{
			$found = true;
			
		}
		
	}
	
}

if ( $found )
{
	// do nothing - there's already a cron job for this
	fwrite($logHandle, date("Y-m-j H:i") . " - INFO - Nothing new found - ending\n***\n");
	//pushover("nothing to do - cron entry found", "Recorder no change");
	
} else {
	// add a cron job for this
	$fp = fopen("/tmp/newcron.txt", "w");
	
	for ( $i=0; $i<count($cronContents); $i++ )
	{
		fwrite($fp, $cronContents[$i] . "\n");
		
	}
	
	fwrite($fp, $newCronString."\n");
	fclose($fp);
	
	exec("crontab /tmp/newcron.txt", $cronModifyResult, $retVal);
	
	if ( $retVal == 0 )
	{
		fwrite($logHandle, date("Y-m-j H:i") . " - INFO - New gig found, successfully wrote crontab - ending\n***\n");
		$message="Successfully wrote crontab:\nArtist: $artist\nDate: $when\nDescription: $description";
		pushover($message, "New gig!");
		
	} else {
		fwrite($logHandle, date("Y-m-j H:i") . " - CRIT - New gig found, failed to write new crontab - ending\n***\n");
		pushover("failed to write new crontab", "Recorder failure!");
		
	}
	
}

/*	
Minute   Hour   Day of Month       Month          Day of Week        Command

*    *    *    *    *    *
-    -    -    -    -    -
|    |    |    |    |    |
|    |    |    |    |    + year [optional]
|    |    |    |    +----- day of week (0 - 7) (Sunday=0 or 7)
|    |    |    +---------- month (1 - 12)
|    |    +--------------- day of month (1 - 31)
|    +-------------------- hour (0 - 23)
+------------------------- min (0 - 59)
*/
function date_to_cron($date)
{
	$earlier = date_modify($date, "-5 minutes");
	$cron["min"] = date_format($earlier, "i");
	$cron["hour"] = date_format($earlier, "H");
	$cron["day"] = date_format($earlier, "j");
	$cron["month"] = date_format($earlier, "n");
	$cron["dayOfWeek"] = "*";
	$cron["year"] = date_format($earlier, "Y");
	
	return $cron;
	
}

function cron_to_date($cron)
{
	$inCron = explode(" ", $cron);
	$when = $inCron[0] . " " . $inCron[1] . " " . $inCron[2] . " " . $inCron[3] . " " . date("Y");
	$date = DateTime::createFromFormat('i H j n * Y', $when); 
	return $date;
	
}

/*
POST an HTTPS request to https://api.pushover.net/1/messages.json with the following parameters:
token (required) - your application's API token
user (required) - the user/group key (not e-mail address) of your user (or you), viewable when logged into our dashboard (often referred to as USER_KEY in our documentation and code examples)
message (required) - your message
Some optional parameters may be included:
device - your user's device name to send the message directly to that device, rather than all of the user's devices (multiple devices may be separated by a comma)
title - your message's title, otherwise your app's name is used
url - a supplementary URL to show with your message
url_title - a title for your supplementary URL, otherwise just the URL is shown
priority - send as -2 to generate no notification/alert, -1 to always send as a quiet notification, 1 to display as high-priority and bypass the user's quiet hours, or 2 to also require confirmation from the user
timestamp - a Unix timestamp of your message's date and time to display to the user, rather than the time your message is received by our API
sound - the name of one of the sounds supported by device clients to override the user's default sound choice
*/
function pushover($message, $title)
{
	curl_setopt_array($ch = curl_init(), array(
		curl_setopt($ch, CURLOPT_HEADER, false),
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false),
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true),
		CURLOPT_URL => "https://api.pushover.net/1/messages.json",
		CURLOPT_POSTFIELDS => array(
			"token" => "aJcfJv8iqShDjjwXdg5A5eCRbwqvsH",
			"user" => "gCcFJwgAw48scLCTiR9Q2om92jGqUP",
			"message" => $message,
			"title" => $title
		)
	));
	
	return curl_exec($ch);
	
}

?>
