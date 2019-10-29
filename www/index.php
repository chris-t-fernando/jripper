<html>
<head>
	<title>Recording manager</title>
	<meta name="viewport" content="initial-scale=1.0">
	<link rel="stylesheet" href="https://code.jquery.com/mobile/1.4.5/jquery.mobile-1.4.5.min.css"></link>
	<script type="text/javascript" src="https://code.jquery.com/jquery-2.1.0.min.js"></script>
	<script>
		$(document).bind('mobileinit',function(){
			$.mobile.changePage.defaults.changeHash = false;
			$.mobile.hashListeningEnabled = false;
			$.mobile.pushStateEnabled = false;
		});
		
		Number.prototype.toHHMMSS = function () {
			var sec_num = parseInt(this, 10); // don't forget the second param
			var hours   = Math.floor(sec_num / 3600);
			var minutes = Math.floor((sec_num - (hours * 3600)) / 60);
			var seconds = sec_num - (hours * 3600) - (minutes * 60);
			var decimal = Math.floor((this - (hours * 3600) - (minutes * 60) - seconds) * 10);
			/*if ( eval(this.toString().length - this.toString().replace(".","").length) == 1 )
			{
				var decimal = this.toString().substring(this.toString().indexOf(".")+1, 4)
				
			} else {
				var decimal = "00";
				
			}*/
			
			if (hours   < 10) {hours   = "0"+hours;}
			if (minutes < 10) {minutes = "0"+minutes;}
			if (seconds < 10) {seconds = "0"+seconds;}
			if (decimal < 10) {decimal = decimal+"0";}
			var time    = hours+':'+minutes+':'+seconds+"."+decimal;
			//var time    = hours+':'+minutes+':'+seconds;
			
			return time;
		}
		
		function unformat(inTime)
		{
			// h h   m m   s s   m m
			// 0 0 : 0 0 : 0 0 . 0 0
			// 0 1 2 3 4 5 6 7 8 9 10
			var hours = inTime.substring(0, 2);
			var minutes = inTime.substring(3, 5);
			var seconds = inTime.substring(6, 8);
			var decimal = inTime.substring(8, 11);
			
			var time = eval(eval(hours * 3600) + eval(minutes * 60) + eval(seconds));
			//+ eval(decimal)
			//console.log(inTime);
			//console.log(hours);
			//console.log(minutes);
			//console.log(seconds);
			//console.log(decimal);
			return time;
			
		}
		
		function moveStart()
		{
			document.getElementById('startBar').style.left=eval(eval(unformat(document.getElementById('startTime').value)/document.getElementById('track').duration*660)+3)+'px';
			return false;
		}
		
		function moveEnd()
		{
			document.getElementById('endBar').style.left=eval(eval(unformat(document.getElementById('endTime').value)/document.getElementById('track').duration*660)+3)+'px';
			return false;
		}
		
		function handleEvent(e){
			var elem, evt = e ? e:window.event;
			clickX = evt.pageX;
			//eval(eval(unformat(document.getElementById('endTime').value)/document.getElementById('track').duration*660)+3)
			//document.getElementById('endTime').value=Math.floor(eval(eval(clickX - 3) / 660 * document.getElementById('track').duration));
			document.getElementById('track').currentTime = Math.floor(eval(eval(clickX - 3) / 660 * document.getElementById('track').duration));
			
		}
		
	</script> 
	<script type="text/javascript" src="https://code.jquery.com/mobile/1.4.5/jquery.mobile-1.4.5.min.js"></script>
	<style>
		.ui-btn { padding-top: 1px; padding-bottom: 1px; font-size: 15px; width: 70px; margin-top: 1px; margin-bottom: 1px; margin: 1px;}
		.ui-input-text { margin-top: 1px; margin-bottom: 1px; margin: 1px; }
		.Csmall { padding-top: 1px; padding-bottom: 1px; height: 10px; font-size: 10px; text-align: right; }
		body {
			background-color: black;
		}
	</style>
</head>
<body style="margin: 0;padding: 0; overflow-y: hidden;">
<form name="processForm">
	<table border="0">
		<tr>
			<td>
				<div id="files" style="-webkit-overflow-scrolling: touch; overflow: auto; overflow-y: scroll; height:126px; width:300px">
					<font size="1">
						<b>FILES</b><br />
<?

$dir = "/triplej/www/stage1";
$rawFiles = scandir($dir);

$first="";

for ( $i=0; $i<count($rawFiles); $i++ )
{
	if ( substr($rawFiles[$i], -4) == ".mp3" )
	{
		echo "<a onclick=\"document.getElementById('spectrum').src='image.php?src=" . urlencode($rawFiles[$i]) . "';document.getElementById('track').src='stage1/" . $rawFiles[$i] . "';document.getElementById('track').load();\">" . $rawFiles[$i] . "</a><br />\r\n";
		//echo "<a onclick=\"document.getElementById('track').play();\">" . $rawFiles[$i] . "</a><br />\r\n";
		
		if ( $first == "" )
		{
			$first = $rawFiles[$i];
			
		}
		
	}
	
}

?>
					</font>
				</div>
			</td>
			<td>
				<div id="controls" style="-webkit-overflow-scrolling: touch; overflow: auto; overflow-y: hidden; height:126px; width:350px">
					<table width="100%" style="margin: 0;padding: 0;">
						<tr>
							<td width="25%">
								<button class="ui-btn ui-corner-all" onclick="document.getElementById('track').play();return false;">Play</button>
							</td>
							<td width="25%">
								<button class="ui-btn ui-corner-all" onclick="document.getElementById('track').pause();return false;">Pause</button>
							</td>
							<td width="25%">
								<button class="ui-btn ui-corner-all" onclick="document.getElementById('track').currentTime-=30.0;return false;">&lt; 30s</button>
							</td>
							<td width="25%">
								<button class="ui-btn ui-corner-all" onclick="document.getElementById('track').currentTime+=30.0;return false;">30s &gt;</button>
							</td>
						</tr>
						<tr>
							<td colspan="2">
								<input type="text" class="Csmall" id="startTime" input="startTime" onchange="moveStart()" style="text-align: center">
							</td>
							<td colspan="1">
								<button class="ui-btn ui-corner-all" onclick="document.getElementById('startTime').value=document.getElementById('track').currentTime.toHHMMSS();moveStart();return false;">Grab</button>
							</td>
							<td colspan="1">
								<button class="ui-btn ui-corner-all" onclick="document.getElementById('track').currentTime=unformat(document.getElementById('startTime').value);return false;">Go</button>
							</td>
						</tr>
						<tr>
							<td colspan="2">
								<input type="text" class="Csmall" id="endTime" input="endTime" onchange="moveEnd()" style="text-align: center">
							</td>
							<td colspan="1">
								<button class="ui-btn ui-corner-all" onclick="document.getElementById('endTime').value=document.getElementById('track').currentTime.toHHMMSS();moveEnd();return false;">Grab</button>
							</td>
							<td colspan="1">
								<button class="ui-btn ui-corner-all" onclick="document.getElementById('track').currentTime=unformat(document.getElementById('endTime').value);return false;">Go</button>
							</td>
						</tr>
						<tr>
							<td colspan="2">
								<input type="text" class="Csmall" id="thisTime" style="text-align: center" value="00:00:00.00">
							</td>
							<td colspan="2">
								<input type="submit" value="Process" id="Process" name="Process" class="ui-btn ui-corner-all" onclick="window.location.href='process.php?start='+unformat(document.getElementById('startTime').value)+'&end='+unformat(document.getElementById('endTime').value)+'&track='+encodeURIComponent(document.getElementById('track').src);"></input>
							</td>
						</tr>
					</table>
				</div>
			</td>
		</tr>
		<tr>
			<td colspan="2">
				<font size="2">
					<img src="image.php?src=<? echo urlencode($first); ?>" width="660" height="190" onclick="handleEvent(event)" id="spectrum" border="1" bordercolor="black">
					<img src="assets/bar.jpg" id="startBar" style="position:absolute;top: 134px;left: 3px;" id="pointer" height="190" width="1">
					<img src="assets/bar.jpg" id="endBar" style="position:absolute;top: 134px;left: 3px;" id="pointer" height="190" width="1">
					<img src="assets/pointer.jpg" style="position:absolute;top: 134px;left: 3px;" id="pointer" height="190" width="1">
					<audio id="track" src="stage1/<? echo $first; ?>" ontimeupdate="document.getElementById('thisTime').value=document.getElementById('track').currentTime.toHHMMSS();document.getElementById('pointer').style.left=(this.currentTime/this.duration*660+3)+'px';"">
						<p>Your browser does not support the audio element</p>
					</audio>
				</font>
			</td>
		</tr>
	</table>
</form>

</body>
</html>
