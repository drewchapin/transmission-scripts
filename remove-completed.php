#!/usr/bin/php
<?php

/*******************************************************************************
 * Copyright (C) 2016 - Drew Chapin <druciferre@gmail.com>                     *
 *                                                                             *
 * This program is free software: you can redistribute it and/or modify        *
 * it under the terms of the GNU General Public License as published by        *
 * the Free Software Foundation, either version 3 of the License, or           *
 * (at your option) any later version.                                         *
 *                                                                             *
 * This program is distributed in the hope that it will be useful,             *
 * but WITHOUT ANY WARRANTY; without even the implied warranty of              *
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the               *
 * GNU General Public License for more details.                                *
 *                                                                             *
 * You should have received a copy of the GNU General Public License           *
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.       *
 ******************************************************************************/

require_once("transmission-rpc.class.php");

// Read settings
$path = realpath(dirname(__FILE__)) . "/transmission.json";
$settings = json_decode(file_get_contents($path));
if( json_last_error() != JSON_ERROR_NONE )
	die("Error reading settings file.");

$rpc = new TransmissionRPC($settings->server,$settings->port,$settings->username,$settings->password);
foreach( $rpc->getSessionStats(array("id","name","isFinished")) as $torrent )
{
	if( $torrent["isFinished"] === true )
	{
		if( $rpc->removeTorrent($torrent["id"]) )
			echo "Removed " . $torrent["name"] . PHP_EOL;
	}
}

?>
