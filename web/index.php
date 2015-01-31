<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="de">
<head>
<title>Sensor Infos</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<meta name="robots" content="DISALLOW">
<style type=text/css>
 body { font-size: 8pt; color: black; font-family: Verdana,arial,helvetica,serif; margin: 0 0 0 0; }
 .style1 {
	color: #999999;
	font-weight: bold;
 }
</style>
</head>
<body style="background-image:none">


<?php
//------------------------------------------------------------------------------
require_once("config.php");
require_once("functions.php");
//------------------------------------------------------------------------------
include("jpgraph-3.5.0b1/src/jpgraph.php");
include("jpgraph-3.5.0b1/src/jpgraph_line.php");
require_once("jpgraph-3.5.0b1/src/jpgraph_date.php");
//------------------------------------------------------------------------------
error_reporting(E_ALL);
ini_set('track_errors', 1);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set("memory_limit","64M");
ini_set("max_execution_time","30");
@ob_implicit_flush(true);
@ob_end_flush();
$_SELF=$_SERVER['PHP_SELF'];
$s="&#160;";
$DURATION_start = microtime(true);
#$DEBUG=1;
//------------------------------------------------------------------------------
if (!isset($GRAPH['SetImgFormat']) OR empty($GRAPH['SetImgFormat'])) { $GRAPH['SetImgFormat'] = "png"; }
if ($GRAPH['SetImgFormat'] != "png" OR $GRAPH['SetImgFormat'] != "jpeg" OR $GRAPH['SetImgFormat'] != "gif") { $GRAPH['SetImgFormat'] = "png"; }
if (!isset($GRAPH['DIR']) OR empty($GRAPH['DIR'])) { $GRAPH['DIR'] = "."; }
if (!is_dir($GRAPH['DIR'])) { mkdir($GRAPH['DIR']); @exec("mount -t tmpfs tmpfs ".$GRAPH['DIR']." -o defaults"); }
//------------------------------------------------------------------------------
if (isset($_GET['debug']) && $_GET['debug'] == 1) { $DEBUG = 1; }
if (isset($DEBUG) and $DEBUG == 1) {
	echo "<div id='DEBUG'>\n";
	echo "<b class=\"fett\">--- DEBUG ---</b><br/>\n";
	echo "<b>_GET</b>\n";
	showarray($_GET);
	echo "<b>_POST</b>\n";
	showarray($_POST);
	echo "<b class=\"fett\">-------------</b>\n";
	echo "</div>\n";
}
//------------------------------------------------------------------------------
if (!file_exists($DBfile)) { echo "<center>need input! :)</center>\n"; _exit(); }
$now = time();
$Nunc = date('Y-m-d',$now);
$SelectedNodes = isset($_POST["nodeID"]) ? $_POST["nodeID"] : "";
if (isset($_GET['getdata'])) { $SelectedNodes[0] = $_GET['getdata']; }
$GetDATE = isset($_POST["date"]) ? $_POST["date"] : $Nunc;
//------------------------------------------------------------------------------

if ($GetDATE == $Nunc) { $DisplayDATE = "Today"; } else { $DisplayDATE = $GetDATE; }
$DAYSBACK = "7";

$db = db_con($DBfile);

echo "<center>\n";
echo "<br/>\n";
if (!isset($_GET['getdata'])) {
echo "<form action='' method='POST' name='NodesForm'>\n";
echo "<table border='0'>\n";
echo "<caption><b>Select Sensor to show:</b></caption>\n";
$i=0;
$MAXROW=5;
$q = db_query("SELECT nodeID,place FROM werte WHERE 1 GROUP BY nodeID ORDER BY nodeID ASC");
while ($res = $q->fetch(PDO::FETCH_ASSOC)) {
	$CHECKED="";
	if (!empty($SelectedNodes)) {
		$FoundChecked=0;
		foreach ($SelectedNodes AS $node_id) {
			if ($node_id == $res['nodeID']) { $FoundChecked=$node_id; }
		}
		if ($FoundChecked != 0) { $CHECKED="checked='checked'"; }
	}
	if ($i == 0) { echo "<tr>\n"; }
	echo "<td><input type='checkbox' name='nodeID[]' value='".$res['nodeID']."' ".$CHECKED." />$s".$res['place']."$s</td>\n";
	$i++;
	if ($i == $MAXROW) { echo "</tr>\n"; $i=0; }
}
unset($FoundChecked);
unset($node_id);
unset($res);
echo "</table>\n";

echo "<p>\n";
echo "<select name='date'>\n";
echo "  <option value='".$Nunc."' selected='selected'>".$Nunc."</option>\n";
for ($i = 1; $i <= $DAYSBACK; $i++) {
	$tmpday = date('d',$now) - 1;
	if ($tmpday <= "0") {
		$date = date('Y-m-d',mktime(0, 0, 0, date("m")-1, date("d")-$i, date("Y")));
	} else {
		$date = date('Y-m-d',mktime(0, 0, 0, date("m"), date("d")-$i, date("Y")));
	}
	echo "  <option value='".$date."'>".$date."</option>\n";
}
echo "</select>\n";
echo "</p>\n";
echo "<p><input type='submit' value='Go' name='ShowNodes'/></p>\n";
echo "</form>\n";

} // end: if (isset($_GET['getdata']))

// dont continue if no nodeID selected
if (empty($SelectedNodes)) { _exit(); }


$SQL0="SELECT time,nodeID,place,supplyV,temp,hum,date(time,'unixepoch') AS Date FROM werte WHERE Date='".$GetDATE."' AND (";
$SQL1="";
foreach ($SelectedNodes AS $NID) {
	if (empty($SQL1)) { $SQL1 = "nodeID='".$NID."'"; } else { $SQL1 .= " OR nodeID='".$NID."'"; }
	GenerateGraph($NID);
	if (!isset($_GET['getdata'])) { echo "<img src='".$GRAPH['DIR']."/".$NID.".".$GRAPH['SetImgFormat']."' /> <br/>\n"; }
}
$SQL2=") ORDER BY time DESC";

echo "<div id='SensorDetails'>\n";
echo "<table class='Sensors' border=1 cellpadding=2 cellspacing=0 bordercolorlight>\n";
echo "<tr>\n";
echo "<th class='tab'>Date</th>\n";
echo "<th class='tab'>Sensor</th>\n";
echo "<th class='tab'>Supply Voltage</th>\n";
echo "<th class='tab'>Temperature</th>\n";
echo "<th class='tab'>Humidity</th>\n";
echo "</tr>\n";
// get Added Entries
$q = db_query("".$SQL0." ".$SQL1." ".$SQL2."");
while ($result = $q->fetch(PDO::FETCH_ASSOC)) {
	$datetime = date('H:i:s d.m.Y',$result['time']);
	$nodeID = $result['nodeID'];
	$place = $result['place'];
	$supplyV = $result['supplyV'] / 1000;
	$temp = $result['temp'] / 100;
	if (!empty($result['hum'])) { $humi = $result['hum'] / 100; } else { $humi = "$s"; }
	echo "<tr>\n";
	echo "<td class='tab1' align='CENTER'>$s ".$datetime." $s</td>\n";
	echo "<td class='tab1' align='CENTER'>$s ".$place." $s</td>\n";
	echo "<td class='tab1' align='CENTER'>$s ".$supplyV."V $s</td>\n";
	echo "<td class='tab1' align='CENTER'>$s ".$temp."&deg;C $s</td>\n";
	if (!empty($humi)) { echo "<td class='tab1' align='CENTER'>$s ".$humi."% $s</td>\n"; } else { echo "<td class='tab1' align='CENTER'> $s </td>\n"; }
	echo "</tr>\n";
}
echo "</table></div>\n";


echo "<br/><br/><br/>";
$DURATION_end = microtime(true);
$DURATION = $DURATION_end - $DURATION_start;
echo "<p><font size='0'>Page generated in ".round($DURATION, 3)." seconds</font></p>\n";

?>
</body>
</html>