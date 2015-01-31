<?php
//------------------------------------------------------------------------------
error_reporting(E_ALL);
ini_set('track_errors', 1);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
//------------------------------------------------------------------------------
require_once("config.php");
require_once("functions.php");
//------------------------------------------------------------------------------

// check if sqlite db file exists else create it..
if (!file_exists($DBfile)) {
	$db = db_con($DBfile);
	$SQL = "CREATE TABLE IF NOT EXISTS werte (id INTEGER PRIMARY KEY,time INT,nodeID INT,place TEXT,supplyV TEXT,temp TEXT,hum TEXT,pressure TEXT,height TEXT)";
	$create = db_query($SQL);
}

if (!empty($_GET)) {
	$DATA=array();
	$ValidKey = false;
	foreach ($_GET AS $arg => $var) {
		$Found = 0;
		if ($arg == "key" AND $var == $SECURITYKEY) { $ValidKey=true; $Found = 1; }
		if ($arg == "node") { $DATA["nodeID"] = $var; $Found = 1; }// Node ID
		if ($arg == "v") { $DATA["supplyV"] = $var; $Found = 1; }  // Supply Voltage
		if ($arg == "t") { $DATA["temp"] = $var; $Found = 1; }     // Temperatur
		if ($arg == "h") { $DATA["hum"] = $var; $Found = 1; }      // Humidity (Luftfeuchte)
		if ($arg == "p") { $DATA["pressure"] = $var; $Found = 1; } // Pressure (Luftdruck)
		if ($arg == "he") { $DATA["height"] = $var; $Found = 1; }  // Height (Höhe)

		if ($Found == 0) { $DATA[$arg] = $var; }
	}
	if (!$ValidKey) { echo "Invalid Key!"; exit(); }

	if (isset($DATA["nodeID"]) AND !empty($DATA["nodeID"])) {
		$VALnames="";
		$VALUES="";
		foreach ($DATA AS $NAME => $VAR) {
			$VALnames.= "".$NAME.",";
			$VALUES.= "'".$VAR."',";
		}
		$VALnames=substr_replace($VALnames,"",strrpos($VALnames,","));
		$VALUES=substr_replace($VALUES,"",strrpos($VALUES,","));

		$SQL ="INSERT INTO werte (place,time,";
		$SQL.="".$VALnames.")";
		$SQL.=" VALUES ('".$Sensor[$DATA["nodeID"]]."','".time()."',".$VALUES."";
		$SQL.=");";
		$db = db_con($DBfile);
		$insert = db_query($SQL);
	}
}

?>