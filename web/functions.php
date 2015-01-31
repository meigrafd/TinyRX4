<?php

//needed to fix STRICT warnings about timezone issues
$tz = exec('date +%Z');
@date_default_timezone_set(timezone_name_from_abbr($tz));
ini_set('date.timezone',@date_default_timezone_get());



function _exit() {
	echo "</body>";
	echo "</html>";
	exit();
}

//______________________________________________________________________________________
// sqlite

// DB connect
function db_con($DBfile) {
	if (!$db = new PDO("sqlite:$DBfile")) {
		$e="font-size:23px; text-align:left; color:firebrick; font-weight:bold;";
		echo "<b style='".$e."'>Fehler beim öffnen der Datenbank:</b><br/>";
		echo "<b style='".$e."'>".$db->errorInfo()."</b><br/>";
		die;
	}
	$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	return $db;
}

// DB Query
function db_query($sql) {
	global $db;
	$result = $db->query($sql) OR db_error($sql,$db->errorInfo());
	return $result;
}

//Function to handle database errors
function db_error($sql,$error) {
	die('<small><font color="#ff0000"><b>[DB ERROR]</b></font></small><br/><br/><font color="#800000"><b>'.$error.'</b><br/><br/>'.$sql.'</font>');
}

//______________________________________________________________________________________

// generate graph's
function GenerateGraph($nodeID) {
	global $DBfile,$GRAPH,$DisplayDATE,$GetDATE;
	$i=0;
	$db = db_con($DBfile);
	$query = $db->query("SELECT place,time,supplyV,temp,hum,date(time,'unixepoch') AS Date FROM werte WHERE Date='".$GetDATE."' AND nodeID='".$nodeID."'");
	while($result = $query->fetch(PDO::FETCH_ASSOC)){
		$place = $result['place'];
		$TIME[$i] = $result['time'];
		$TEMP[$i] = $result['temp'] / 100;
		$HUM[$i] = $result['hum'] / 100;
		$SUPPLYV[$i] = $result['supplyV'] / 1000;
		$i++;
	}
	if ($i == 0) { return; }
	// Create the graph. These two calls are always required
	$graph = new Graph(1100,350);
	$graph->SetScale("datlin");
	$graph->SetShadow();
	$graph->SetMargin(50,50,20,100);

	$graph->title->Set(''.$place.': '.$DisplayDATE.'');
	$graph->title->SetFont(FF_FONT1,FS_BOLD);

	//$graph->xaxis->title->Set("Zeit");
	//$graph->xaxis->title->SetFont(FF_FONT1,FS_BOLD);
	$graph->yaxis->title->Set("Temperatur °C");
	$graph->yaxis->SetTitlemargin(40);
	$graph->yaxis->title->SetFont(FF_FONT1,FS_BOLD);

	$graph->xaxis->SetLabelFormatCallback('TimeCallback');
	$graph->xaxis->SetLabelAngle(90);
	$graph->xaxis->scale->SetTimeAlign(MINADJ_15);

	$lineplot = new LinePlot($TEMP,$TIME);
	$lineplot->SetColor("red");
	$lineplot->SetLegend("Temperature ");

	$lineplotb = new LinePlot($HUM,$TIME);
	$lineplotb->SetColor("blue");
	$lineplotb->SetWeight(2);
	$lineplotb->SetLegend("Humidity ");

	$graph->SetY2Scale("lin");
	$graph->AddY2($lineplotb);
	$graph->y2axis->title->Set("Luftfeuchte % ");
	$graph->y2axis->title->SetColor("blue");
	$graph->y2axis->SetTitlemargin(40);

	// Add the plot to the graph
	$graph->Add($lineplot);
	// Adjust the legend position
	$graph->legend->Pos(0.5,0.98,"center","bottom");

	// Display the graph
	//$graph->Stroke();

	// generate image file for HTML pages
	// Get the handler to prevent the library from sending the
	// image to the browser
	$gdImgHandler = $graph->Stroke(_IMG_HANDLER);  
	$graph->img->SetImgFormat($GRAPH['SetImgFormat']);
	$fileName = "".$GRAPH['DIR']."/".$nodeID.".".$GRAPH['SetImgFormat']."";
	$graph->img->Stream($fileName);
		
	unset($TIME);
	unset($TEMP);
	unset($HUM);
	unset($SUPPLYV);
}

function TimeCallback($aVal) {
	return Date('H:i    ',$aVal);
}


//______________________________________________________________________________________

function showarray($array) {
	echo "<pre><b style='font-size:13px; text-align:left; color:#c8c8c8;'>\n";
	var_dump($array);
	echo "</b>\n";
	flush();
}
// showvar(get_defined_vars(),_SELF);
function showvar($systemDefinedVars,$varName) {
	echo "<b style='font-size:13px; text-align:left; color:#c8c8c8;'>\n";
	foreach ($systemDefinedVars as $var => $value) {
		if ($varName == $var) { echo "$var: $systemDefinedVars[$var]"; break; }
	}
	echo "</b><br/>\n";
	flush();
}
function _debug_($systemDefinedVars,$VAR) {
	echo "<b style='font-size:13px; text-align:left; color:#c8c8c8;'>\n";
	if (is_array($VAR)) {
		var_dump($VAR);
	} else {
		foreach ($systemDefinedVars as $var => $value) {
			if ($varName == $var) { echo "$var: $systemDefinedVars[$var]"; }
		}
	}
	echo "</b><br/>\n";
	flush();
}

?>