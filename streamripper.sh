#!/bin/bash

# setup
echo "Starting streamripper.sh..." >> /tmp/record.log
STREAM="http://www.abc.net.au/res/streaming/audio/aac/triplej.pls"
NOW=$(date +"%Y-%m-%d")
YEAR=$(date +"%Y")
ARTIST=$1
ALBUM="Live at the Wireless"
TITLE="$ALBUM $NOW"
DURATION=3900
BASEDIR="/triplej/www/stage0"

# start recording
cd $BASEDIR
streamripper $STREAM -u FreeAmp/2.x -l $DURATION -a "$ARTIST - $TITLE" -d .

# search @triplejplays twitter to grab artist and title
assignment_string=$(python /triplej/searchTimeline.py)
eval $assignment_string

# variable to include any errors notes in Pushover notification
ERRORNOTES=""
if [ "$TWITTERERROR" = "0" ]; then
	# twitter search found LATW tweet
	mv "$ARTIST - $TITLE.aac" "$NEWARTIST - $NEWTITLE.aac"
	ARTIST=$NEWARTIST
	TITLE=$NEWTITLE

elif [ "$TWITTERERROR" = "1" ]; then
	# didn't find any LATW tweets - don't change the ARTIST or TITLE variables but ping me
	ERRORNOTES="
Errors encountered, falling back to default artist and title
Error message: ${TWITTERERRORMSG}"
	
elif [ "$TWITTERERROR" = "2" ]; then
	# found LATW tweet but couldn't parse it - bit more error information in this case
	# don't change variables and ping me
	ERRORNOTES="
Errors encountered, falling back to default artist and title
Error message: ${TWITTERERRORMSG}
Raw tweet: ${TWITTERTEXT}"

else
	# something went wrong in the python script - output couldn't be parsed
	# don't change variables and ping me
	ERRORNOTES="
Errors encountered in Python, falling back to default artist and title
Errors: ${assignment_string}"
	
fi


# convert to mp3
# todo - get rid of ffmpeg id3 tagging
ffmpeg -i "$ARTIST - $TITLE.aac" -ac 2 -ab 64k -ar 44100 -metadata title="$TITLE" -metadata artist="$ARTIST" -metadata album="$ALBUM" "$ARTIST - $TITLE.mp3"

# write ID3 tags
eyeD3  --text-frame=TCMP:1 -c "$2" -a "$ARTIST" -A "$ALBUM" -b "Triple J" -t "$TITLE" -Y "$YEAR" --release-date "$NOW" "$ARTIST - $TITLE.mp3"

# move recording to next stage and cleanup aac
mv "$ARTIST - $TITLE.mp3" /triplej/www/stage1
rm "$ARTIST - $TITLE.aac"

# generat spectrum analyser
sox "/triplej/www/stage1/$ARTIST - $TITLE.mp3" -n trim 0 1 stats : restart 2> "/triplej/www/stage1/$ARTIST - $TITLE.mp3.spectrum"

# tell me that its all done
curl -s --form-string "token=aJcfJv8iqShDjjwXdg5A5eCRbwqvsH" --form-string "user=gCcFJwgAw48scLCTiR9Q2om92jGqUP" --form-string "message=Finished writing LATW $ARTIST $TITLE ${ERRORNOTES}"  --form-string "url=http://j.diamonds.ro.lt/" --form-string "url_title=Editor" https://api.pushover.net/1/messages.json
