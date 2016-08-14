# transmission-scripts

A couple of scripts I've written to work with the <a href="https://www.transmissionbt.com/" target="_blank">Transmission BitTorrent daemon</a>.

## Scripts

#### remove-completed.php
Removes any torrents from the queue that have reached their max seed ratio (AKA completed). 

#### showrss.php
Fetches an RSS feed specified in the config file, and adds any torrent that has been added to the feed since the last run of the script. 


## Setup

#### Configuration

Put the the scripts somewhere on your Transmission server, and create a `transmission.json` config file. 

	{
		"username": "<username>",
		"password": "<password>",
		"server": "<server>",
		"port": 9091,
		"showrss_feed": "http:\/\/showrss.info\/user\/<user_id>.rss?magnets=true&namespaces=true&name=clean&quality=null&re=null",
		"showrss_sync": "2016-07-04 04:20:19"
	}

`showrss_sync` should be set to the last time the script was run. For initial setup, you should set this to todays date.

#### Scheduling with Cron

Example crontab.

	 # ┌───────────── min (0 - 59)
	 # │ ┌────────────── hour (0 - 23)
	 # │ │ ┌─────────────── day of month (1 - 31)
	 # │ │ │ ┌──────────────── month (1 - 12)
	 # │ │ │ │ ┌───────────────── day of week (0 - 6) (0 to 6 are Sunday to
	 # │ │ │ │ │                  Saturday, or use names; 7 is also Sunday)
	 # │ │ │ │ │
	   0 * * * *  /home/drew/bin/transmission-scripts/remove-completed.php
	   0 * * * *  /home/drew/bin/transmission-scripts/showrss.php
