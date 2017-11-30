#!/usr/bin/php
<?php

/***************************************************************************
 * Copyright (C) 2016 - Drew Chapin <druciferre@gmail.com>                 *
 *                                                                         *
 * This program is free software: you can redistribute it and/or modify    *
 * it under the terms of the GNU General Public License as published by    *
 * the Free Software Foundation, either version 3 of the License, or       *
 * (at your option) any later version.                                     *
 *                                                                         *
 * This program is distributed in the hope that it will be useful,         *
 * but WITHOUT ANY WARRANTY; without even the implied warranty of          *
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the           *
 * GNU General Public License for more details.                            *
 *                                                                         *
 * You should have received a copy of the GNU General Public License       *
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.   *
 **************************************************************************/

require_once("transmission-rpc.class.php");

// Read settings
$path = __DIR__ . "/transmission.json";
$settings = @file_get_contents($path);
if( $settings === false )
	die("Error reading settings file.");

// Decode settings
$settings = json_decode($settings);
if( json_last_error() != JSON_ERROR_NONE )
	die("Error decoding settings file.");

// Get RSS feed XML
$feed_xml = false;
for( $i = 1; $feed_xml === false && $i <= 10; $i++ )
{
	$feed_xml = @file_get_contents($settings->showrss->feed_url);
	sleep(5);
}
if( $feed_xml === false )
	die("Failed to fetch RSS feed after 10 tries.");
$feed_xml = new SimpleXMLElement($feed_xml);

// Establish connection to Transmission RPC web interface
$rpc = new TransmissionRPC($settings->server,$settings->port,
	$settings->username,$settings->password);

// Loop through each torrent and add to download queue
foreach( $feed_xml->channel->item as $show )
{
	if( isset($settings->showrss->last_sync) && strtotime($show->pubDate) > strtotime($settings->showrss->last_sync) ) 
	{
		echo "Downloading " . $show->title . PHP_EOL;
		if( isset($settings->showrss->download_dir) )
		{
			// Create Download Dir if specified
			$dl_dir = rtrim($settings->showrss->download_dir,DIRECTORY_SEPARATOR);
			$dl_dir .= DIRECTORY_SEPARATOR . $show->children("tv",true)->show_name;
			if( !file_exists($dl_dir) )
				mkdir($dl_dir,0775,true);
			// Add torrent with download dir
			$rpc->addTorrent($show->link,$dl_dir);
		}
		// Add torrent with default download dir
		$rpc->addTorrent($show->link);
		// Send notification email
		if( isset($settings->showrss->notification->email_addr) )
			mail($settings->showrss->notification->email_addr,$settings->showrss->notification->subject,$show->title);
	}
}

// Update sync time
$settings->showrss->last_sync = date(DATE_RSS);
$settings = json_encode($settings,JSON_PRETTY_PRINT);
if( json_last_error() == JSON_ERROR_NONE )
	file_put_contents($path,$settings);

?>
