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

class TransmissionRPC
{
	private $sessionID;
	private $rpc_url;
	private $username;
	private $password;
	public function __construct( $server, $port, $user, $pass)
	{
		$port = isset($port) ? $port : 9091;
		$this->rpc_url = "http://$server:$port/transmission/rpc";
		if( isset($user) )
		{
			$this->username = $user;
			$this->password = $pass;
			$response = $this->curl(); // Authenticate
			if( $response["status_code"] != 409 )
				throw new Exception("Unable to authenticate with server.");
		}
	}
	public function addTorrent( $filename, $download_dir = "", $paused = false )
	{
		$request = array("method"=>"torrent-add","arguments"=>array());
		if( is_readable((string)$filename) )
		{
			$torrent = readfile((string)$filename);
			$request["arguments"]["metainfo"] = base64_encode($torrent);
		}
		else
			$request["arguments"]["filename"] = (string)$filename;
		if( isset($download_dir) )
			$request["arguments"]["download-dir"] = (string)$download_dir;
		$request["arguments"]["paused"] = (bool)$paused;
		$response = $this->curl(json_encode($request));
		return $response["status_code"] == 200;
	}
    private function curl( $request_body = "" )
    {
        $ch = curl_init($this->rpc_url);
        curl_setopt($ch,CURLOPT_CUSTOMREQUEST,"POST");
        curl_setopt($ch,CURLOPT_POSTFIELDS,$request_body);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch,CURLOPT_VERBOSE,0);
        curl_setopt($ch,CURLOPT_HEADER,1);
		$userpwd = $this->username . ":" . $this->password;
        curl_setopt($ch,CURLOPT_USERPWD,$userpwd);
        $request_headers = array("Content-Length: ".strlen($request_body));
		if( isset($this->sessionID) )
			array_push($request_headers,"X-Transmission-Session-Id: "
				.$this->sessionID);
        curl_setopt($ch,CURLOPT_HTTPHEADER,$request_headers);
        $raw_response = curl_exec($ch);
        $status_code = curl_getinfo($ch,CURLINFO_HTTP_CODE);
        $raw_header_size = curl_getinfo($ch,CURLINFO_HEADER_SIZE);
        $raw_headers = substr($raw_response,0,$raw_header_size);
        $headers = array();
        // turn headers into an array
        foreach( explode("\r\n",$raw_headers) as $i => $line )
        {
            $v = explode(": ",$line,2);
            if( count($v) == 2 )
                $headers[$v[0]] = $v[1];
        }
        // get response body in text or as a JSON object
		if( strlen($raw_response) > $raw_header_size )
        {
            $response = substr($raw_response,$raw_header_size);
            $json = json_decode($response,true);
            if( json_last_error() == JSON_ERROR_NONE )
				$response = $json;
        }
        else
			$response = NULL;
        curl_close($ch);
        // Update session ID
        if( isset($headers["X-Transmission-Session-Id"]) )
        	$this->sessionID = $headers["X-Transmission-Session-Id"];
        return compact("status_code","headers","response");
    }
    public function getSessionStats( $fields )
    {
		$request = array(
			"method" => "torrent-get",
			"arguments" => array("fields"=>array())
		);
		if( is_array($fields) )
			$request["arguments"]["fields"] = $fields;
		else
			array_push($request["arguments"]["fields"],$fields);
		$response = $this->curl(json_encode($request));
		if( $response["status_code"] == 200 )
			return $response["response"]["arguments"]["torrents"];
		return null;
    }
	public function removeTorrent( $ids )
	{
		$request = array( 
			"method" => "torrent-remove", 
			"arguments" => array("ids"=>array()) 
		);
		if( is_array($ids) )
			$request["arguments"]["ids"] = $ids;
		else
			array_push($request["arguments"]["ids"],$ids);
		$response = $this->curl(json_encode($request));
		return $response["status_code"] == 200;
	}
}

?>
