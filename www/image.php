<?
error_reporting(E_ALL ^ E_WARNING ^ E_NOTICE);

$hSox = fopen("stage1/" . $_GET["src"] . ".spectrum", "r");

$fInterval = 1;
$fPos = 0;
$aSpectrum = array();

// read in spectrum data
if ($hSox)
{	
	fgets($hSox); # ignore the first row, its just headings
	
	while (($buffer = fgets($hSox)) !== false)
	{
		if ( substr_count($buffer, "RMS Pk dB") )
		{
			$aBuffer = preg_replace('/\s+/', ' ', trim($buffer));
			$aLine = explode(" ", $aBuffer);
			
			$aSpectrum[$fPos]["max"] = $aLine[3];

			//$fPos += $fInterval;
			
		}
		
		if ( substr_count($buffer, "RMS Tr dB") )
		{
			$aBuffer = preg_replace('/\s+/', ' ', trim($buffer));
			$aLine = explode(" ", $aBuffer);
			
			$aSpectrum[$fPos]["min"] = $aLine[3];

			$fPos += $fInterval;
			
		}
		
	}
	
} else { die("stage1/" . $_GET["src"] . ".spectrum not found"); }

$iImgHeight = 84;
$iImgWidth = 1640;
$imgSpectrum = imagecreate($iImgWidth, $iImgHeight);
$colBackground = imagecolorallocate($imgSpectrum, 192, 192, 192);
$colLine = imagecolorallocate($imgSpectrum, 50, 50, 200);
$colLine2 = imagecolorallocate($imgSpectrum, 100, 100, 220);
imagesetthickness($imgSpectrum, 1);


// run over each interval value to average it
$aResized = array();
$aTmp = array();
$fLastPix = 0;
$fAvgMax = 0;
$fAvgMin = 0;

// # samples / 360 = sample:pixel ratio
$fPixSamples = count($aSpectrum) / $iImgWidth;

$fMax = -11;
$fMin = -11;

for ( $i=0; $i<count($aSpectrum); $i++ )
{
	
	if ( floor($i/$fPixSamples) > $fLastPix )
	{
		for ( $j=0; $j<count($aTmp); $j++ )
		{
			$fAvgMax = $fAvgMax + $aTmp[$j]["max"];
			$fAvgMin = $fAvgMin + $aTmp[$j]["min"];
			
		}
		
		$aResized[floor($i/$fPixSamples)]["max"] = ($fAvgMax / count($aTmp));
		$aResized[floor($i/$fPixSamples)]["min"] = ($fAvgMin / count($aTmp));
		
		if ( ($fAvgMax / count($aTmp)) > $fMax )
		{
			$fMax = ($fAvgMax / count($aTmp));
			
		}
		
		if ( ($fAvgMin / count($aTmp)) < $fMin )
		{
			$fMin = ($fAvgMin / count($aTmp));

		}
		
		//echo "aTmp size: " . count($aTmp) . " fAvgMax: " . $fAvgMax . " fAvgMin: " . $fAvgMin . " fMax: " . $fMax . " fMin: " . $fMin;die;
		
		$fAvgMax = 0;
		$fAvgMin = 0;
		$aTmp = array();
		$fLastPix++;
		
	} else {
		array_push($aTmp, $aSpectrum[$i]);
		
	}
	
}

//$fMax = -14;

// diff between min and max
$fDelta = $fMin - $fMax;
$ratio = $iImgHeight / $fDelta;
//echo "Min: " . $fMin . " Max: " . $fMax . " Diff: " . $fDelta . " Ratio: " . $ratio . "<br>";
//die;

for ( $i=1; $i<count($aResized); $i++ )
{
	//echo (($aResized[$i] - $fMin) * -$ratio) . " " . (84-($aResized[$i] - $fMin) * -$ratio) . "<br>";
	
	//imageline($imgSpectrum, $i, 84, $i, (($aResized[$i]["max"] - $fMin) * -$ratio), $colLine2)
	imageline($imgSpectrum, $i, 84, $i, (84-($aResized[$i]["max"] - $fMin) * -$ratio), $colLine);
	imageline($imgSpectrum, $i, 84, $i, (84-($aResized[$i]["min"] - $fMin) * -$ratio), $colLine2);
	
	//echo "line 1: " . (84-($aResized[$i]["min"] - $fMin)) . " line 2: " . (($aResized[$i]["max"] - $fMin) * -$ratio) . "<br>";
	
}
//die;
header("Content-type: image/png");
imagepng($imgSpectrum);


/*
$my_img = imagecreate( 360, 84 );
$background = imagecolorallocate( $my_img, 0, 0, 255 );
$text_colour = imagecolorallocate( $my_img, 255, 255, 0 );
$line_colour = imagecolorallocate( $my_img, 128, 255, 0 );
imagestring( $my_img, 4, 30, 25, "thesitewizard.com", $text_colour );
imagesetthickness ( $my_img, 5 );
imageline( $my_img, 30, 45, 165, 45, $line_colour );

header( "Content-type: image/png" );
imagepng( $my_img );
imagecolordeallocate( $line_color );
imagecolordeallocate( $text_color );
imagecolordeallocate( $background );
imagedestroy( $my_img );
*/
?>
