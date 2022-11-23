<!-- -*- mode: markdown; -*- -->

# API documentation

RESTful interface is served via HTTP for changing the nickname
associated wtih the nick and getting user listings, both real-time and
historical.

method | endpoint | arguments | description
------ | -------- | --------- | -----------
GET | /v1/visitors | at<br>format | Get visitor nicknames. Time can be given with *at*, defaults to present. Possible *format* options are *text* (default) for human readable list, *iframe* for legacy HTML format for web site, and *json* for JSON format.
GET | /v1/nicks | - | List all nicknames on the system
GET | /v1/nick | ip | Get information about the device (Default IP address: Your IP)
PUT | /v1/nick | nick, flappiness, stealth | Track the device using nickname *nick*. Multiple devices can share the same name. You can also set flappiness and stealth mode.
DELETE | /v1/nick | - | Delete device, do not track anymore

All endpoints except `/v1/nicks` show only your data associated to
the MAC of your device.

## Examples

The following examples operate on API endpoints of Hacklab
Jyväskylä. Change the URL to your site before running in other
networks.

The examples below are piped through `jq` which pretty-prints the
output. You may leave it out if you can natively parse JSON :-)

Get list of visitors:

	curl https://visitors.jkl.hacklab.fi/api/v1/visitors?format=json | jq

Get list of visitors last Tuesday at 8 PM:

	curl "https://visitors.jkl.hacklab.fi/api/v1/visitors?format=json&at=`date +%s -d 'last tuesday 20'`" | jq

Get list of registered nicknames:

	curl https://visitors.jkl.hacklab.fi/api/v1/nicks | jq
	
Get info associated to your device:

	curl https://visitors.jkl.hacklab.fi/api/v1/nick | jq

Set a nickname for your device (you may use the same nickname for all of your devices):

	curl -X PUT https://visitors.jkl.hacklab.fi/api/v1/nick?nick=Hillosipuli

Stop tracking:

	curl -X DELETE https://visitors.jkl.hacklab.fi/api/v1/nick
