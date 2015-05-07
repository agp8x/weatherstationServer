<?php
#v1.5
header("Cache-Control: no-cache, must-revalidate");
$start=microtime(true);
date_default_timezone_set("Europe/Berlin");
include('config.php');
include('lib/DBLib.php');
include('draw.php');
include('function.php');
$html="<!DOCTYPE html>\n<html>\n<head>\n\t<meta charset='UTF-8'>\n\t<title>Temperaturverlauf</title>
\t<style type='text/css'>
\ttable,th,td
\t{
\tborder:1px solid black;border-collapse:collapse;
\t}
\t.today{border: 3px solid red;}
\t.selected{border: 2px solid green;}
\t#calendar{
\t\tposition:absolute;
\t\tright:70px;
\t}
\t#chart{}
\t</style>\n</head>\n<body>\n";
$date;
if(isset($_GET['year'],$_GET['month'])){
	$date=mktime(0,0,0,$_GET['month'],1,$_GET['year']);
}else{
	$date=time();
}
$type=(isset($_GET['type']))? $_GET['type']:'none';


$calendar=drawCalendar($date,$type);#sets also $today
$error=false;
$mode=0;
$num=0;
$date=array();
if(isset($_GET['mode']) && $_GET['mode']=='last'){
	//mode:last X
	$mode=2;
	if(isset($_GET['num'])&&!empty($_GET['num'])&& is_numeric($_GET['num'])){
		$num=$_GET['num'];
	}
	if($num>50000 || $num <1){
		$num=24;
	}
}else if(isset($_GET['mode']) && $_GET['mode']=='month'){
	$mode=3;
	$date=array($_GET['year'],$_GET['month'],0);
	$type="temp";
}else if($type!='none' && ($type=="temp" || $type=="ambi" || $type=="humi" || $type=="baro")){
	//mode: day
	$mode=1;
}else if (isset($_GET['mode']) && $_GET['mode']=='predict'){
	$mode=4;
}else{
	$html.="Keine weiteren Angaben: Heutige Temperatur wird angezeigt";
	$mode=1;
	$type="temp";
}
if($mode==1){
	if(isset($_GET['year']) && isset($_GET['month'])){
		if(!isset($_GET['day'])){
			$mode=3;
			$date=array($_GET['year'],$_GET['month'],1);
		}else{
			$date=array($_GET['year'],$_GET['month'],$_GET['day']);
			$future=false;
			#TODO: replace logic?
			if($_GET['year']>$today[2]){
				$future=true;
			}else{
				if($_GET['month']>$today[1] && $_GET['year']==$today[2]){
					$future=true;
				}else{
					if($_GET['day']>$today[0] && $_GET['month']==$today[1]){
						$future=true;
					}
				}
			}
			if($future){
				$error="Gewähltes Datum liegt in der Zukunft.";
				$mode=0;
			}
		}
	}else{
		$date=array(date("Y"),date("n"),date("j"));
	}
}
$db=new DBLib($database['host'],$database['user'],$database['password'],$database['database']);
if(!$error===false){
	$html.=$error;
}else if($mode != 4){
	$html.=generateChart($today,$mode,$num,$date);
}else{
	include('predict.php');
}
$runtime=microtime(true)-$start;
$html.="<div style='position:fixed;bottom:20px;right:50px;' >Runtime: ".$runtime." s</div>";
$html.=$calendar."</body>";
echo $html;
