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
$settings = json_decode($settings);
if( json_last_error() != JSON_ERROR_NONE )
	die("Error decoding settings file.");
$showrss_sync = isset($settings->showrss_sync) ? 
	strtotime($settings->showrss_sync) : null;

// Get RSS feed
$showrss_feed = false;
for( $i = 1; $showrss_feed === false && $i <= 10; $i++ )
{
	$showrss_feed = @file_get_contents($settings->showrss_feed);
	sleep(5);
}
if( $showrss_feed === false )
	die("Failed to fetch RSS feed after 10 tries.");
$xml = new SimpleXMLElement($showrss_feed);

// Establish connection to Transmission RPC web interface
$rpc = new TransmissionRPC($settings->server,$settings->port,
	$settings->username,$settings->password);

// Loop through each torrent and add to download queue
foreach( $xml->channel->item as $show )
{
	if( !isset($showrss_sync) || strtotime($show->pubDate) > $showrss_sync )
	{
		echo "Downloading " . $show->title . PHP_EOL;
		$rpc->addTorrent($show->link);
	}
}

// Update last run time
$settings->showrss_sync = date("Y-m-d H:i:s",time());
$settings = json_encode($settings,JSON_PRETTY_PRINT);
if( json_last_error() == JSON_ERROR_NONE )
	file_put_contents($path,$settings);

?>
