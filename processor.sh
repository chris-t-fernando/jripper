#!/bin/bash

mv "/triplej/www/stage1/$1" "/triplej/www/stage2" 2>&1>>/tmp/errlog.txt
mv "/triplej/www/stage1/$1.spectrum" "/triplej/www/stage2" 2>&1>>/tmp/errlog.txt
#cp "/triplej/www/stage2/$1" "/triplej/www/stage2/tmp.mp3" 2>&1>>/tmp/errlog.txt

infile=/triplej/www/stage2/$1
outfile=/triplej/www/stage2/out$1

sox "$infile" "$outfile" trim $2 =$3 fade t 2 0 2 2>&1>>/tmp/errlog.txt

#OLDTAGS="$(/usr/local/bin/eyeD3 -v /triplej/www/stage2/tmp.mp3)"

OLDTAGS=`eyeD3 -v "/triplej/www/stage2/$1"`

OLDIFS=$IFS;
IFS=$'\n';
FLAG="false"

title="some title"
artist="some artist"
releasedate=2000
comment="some comment"

for x in ${OLDTAGS}
do
	if [[ $x =~ .*title.* ]]
	then
		title=${x:16:50}
	fi
        if [[ $x =~ .*artist.* ]] && ! [[ $x =~ .*album.* ]]
        then
                artist=${x:17:50}
        fi
        if [[ $x =~ .*release\ date.* ]]
        then
                releasedate=${x:23:50}
        fi
	if [[ $FLAG == "true" ]]
	then
		comment=$x
		FLAG="false"
	fi
        if [[ $x =~ .*Comment.* ]]
        then
                FLAG=true
        fi

done

echo $title $artist $releasedate $comment  2>&1>>/tmp/errlog.txt

echo 2 2>&1>>/tmp/errlog.txt
eyeD3  --text-frame=TCMP:1 -c "$comment" -a "$artist" -A "Live at the Wireless" -b "Triple J" -t "$title" -Y "$releasedate" --release-date "$releasedate" "$outfile"  2>&1>>/tmp/errlog.txt

IFS=$OLDIFS
#yes | rm "/triplej/www/stage2/tmp.mp3"  2>&1>>/tmp/errlog.txt
yes | mv "$outfile" "/triplej/www/stage3/$1"  2>&1>>/tmp/errlog.txt
yes | rm "/triplej/www/stage2/$1"
yes | rm "/triplej/www/stage2/$1.spectrum"

#cp "/triplej/www/stage3/$1" "/triplej/www/online/$1" 
aws s3 mv "/triplej/www/stage3/$1" s3://fdoarchive/chris/rips/

api_key=$(aws ssm get-parameter --name=/jtweets/pushover/api_key --with-decryption | jq -r '.Parameter.Value')
app_id=$(aws ssm get-parameter --name=/jtweets/pushover/app_id | jq -r '.Parameter.Value')

curl -s --form-string "token=$api_key" --form-string "user=$app_id" --form-string "message=Finished processing $artist"  --form-string "url=http://j.diamonds.ro.lt/jjj/stage3/$1" --form-string "url_title=Player" https://api.pushover.net/1/messages.json
