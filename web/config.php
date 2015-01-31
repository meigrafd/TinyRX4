<?php
//------------------------------------------------------------------------------
$DBfile = "/tmp/.db.sensors";
$SECURITYKEY = "23338d373027ce83b1f81b9e9563b629";
$GRAPH['SetImgFormat'] = "png";  //valid: png, jpeg or gif
$GRAPH['DIR'] = "graphs";  //mount -t tmpfs tmpfs DIR -o defaults
//------------------------------------------------------------------------------
// $Sensor[<nodeID>] = "<Place>";
$Sensor['19'] = "Wohnzimmer";
$Sensor['20'] = "Garage / Schuppen";
$Sensor['21'] = "Garten";
//------------------------------------------------------------------------------
$Version="0.61";
//------------------------------------------------------------------------------
?>