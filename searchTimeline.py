import tweepy
import json
from tweepy.parsers import JSONParser
import datetime

def unescape(s):
    s = s.replace("&lt;", "<")
    s = s.replace("&gt;", ">")
    # this has to be last:
    s = s.replace("&amp;", "&")
    return s

# set up return JSON
outJSON = { "artist": "", "song": "", "error": 0, "errorMessage": "", "tweetText": "" }

# pull access token details from SSM parameter store
ssm = boto3.client('ssm')
consumer_key = ssm.get_parameter(Name='/jtweets/tweepy/consumer_key', WithDecryption=True).get("Parameter").get("Value")
consumer_secret = ssm.get_parameter(Name='/jtweets/tweepy/consumer_secret', WithDecryption=True).get("Parameter").get("Value")
access_token = ssm.get_parameter(Name='/jtweets/tweepy/access_token', WithDecryption=True).get("Parameter").get("Value")
access_token_secret = ssm.get_parameter(Name='/jtweets/tweepy/access_token_secret', WithDecryption=True).get("Parameter").get("Value")

# login
auth = tweepy.OAuthHandler(consumer_key, consumer_secret)
auth.set_access_token(access_token, access_token_secret)

# set up tweepy
api = tweepy.API(auth, parser=JSONParser())

# get the most recent triplejplays tweet
search_results = api.user_timeline(screen_name="triplejplays",count=10000)

# for testing only!
#search_results[5]["text"]="Live At The Wireless - Two Door Cinema Club (Splendour In The Grass, Byron Bay Parklands 2017) [20:02]"

# iterate through results, convert timezone and dump out tweet details
for tweet in search_results:
    # keep for debugging only - so can compare  
#    thisText = u' '.join((tweet["user"]["screen_name"],tweet["text"])).encode('utf-8')
#    print tweet["id"] , unescape(thisText)

    # the strings that come through are not unicode for some reason
    tweet["text"]=tweet["text"].encode('utf-8')

	
    # start pulling the string apart to work out if its LATW
    if tweet["text"].lower().find("live at the wireless") > -1:
        # found, now look for time stamp [20:02]
        outJSON["tweetText"] = tweet["text"]
        find=tweet["text"].rfind("[")

        if find > -1:
            # found [, now look for ' - '
            stringNoTime=tweet["text"][:find-1]

            find=stringNoTime.find(" - ")

            if find > -1:
                # found ' - '
                stringNoLATW=stringNoTime[find+3:]

                find=stringNoLATW.find(" (")
                if find > -1:
                # found ' ('
                    artist=stringNoLATW[:find]
                    song=stringNoLATW[find+2:-1]

                    # output variables
                    outJSON["artist"] = artist
                    outJSON["song"] = song
                    break

                else:
                    # didn't find ' ('
#                    outJSON["error"] = 2
#                    outJSON["errorMessage"] = "Couldn't find ( for song tokenizer"
					now=datetime.datetime.now()

					artist=stringNoLATW
					song="Live at the Wireless " + str(now.year)

                    # output variables
					outJSON["artist"] = artist
					outJSON["song"] = song
					break

            else:
                # didn't find ' - '
                outJSON["error"] = 2
                outJSON["errorMessage"] = "Couldn't find ' - ' for LATW-artist tokenizer"
                break

        else:
            # didn't find [
            outJSON["error"] = 2
            outJSON["errorMessage"] = "Couldn't find [ for time played tokenizer"
            break

else:
    # didn't find any LATW tweets
    outJSON["error"] = 1
    outJSON["errorMessage"] = "Couldn't find any LATW tweets"

# print json
#print json.dumps(outJSON, sort_keys=False, indent=4)
#print outJSON

# stupid bash
print 'NEWARTIST="{}";NEWTITLE="{}";TWITTERERROR="{}";TWITTERERRORMSG="{}";TWITTERTEXT="{}"'.format(outJSON["artist"], outJSON["song"], outJSON["error"], outJSON["errorMessage"], outJSON["tweetText"])










#stuff for reference only
#print json.dumps(search_results, sort_keys=True, indent=4)
#print len(search_results)

#from dateutil.parser import parse
#from pytz import timezone
# set up timezone stuff
#brisbaneTimezone = timezone('Australia/Brisbane')
#dateFormat = '%Y-%m-%d %H:%M'
    # convert string time from tweet to Python date type, apply Brisbane timezone and convert back to string
#    convertedTime = parse(tweet["created_at"]).astimezone(brisbaneTimezone).strftime(dateFormat)

#    thisText = u' '.join((convertedTime,tweet["user"]["screen_name"],tweet["text"])).encode('utf-8')
#    print tweet["id"] , unescape(thisText)
