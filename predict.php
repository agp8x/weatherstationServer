<?php

$start=microtime(true);

function sim($a, $b){
	$averageA=average($a,$b);
	$averageB=average($b,$a);
	$div=0.0;
	$sumA=0;
	$sumB=0;
	foreach($a as $i=>$value){
		if(!empty($value) && !empty($b[$i])){
			$div+=($value - $averageA)*($b[$i] - $averageB);
			$sumA+=pow(($value-$averageA), 2);
			$sumB+=pow(($b[$i]-$averageB), 2);
		}
	}
	return ($div / (sqrt($sumA) * sqrt($sumB)));
}
function r($p,$i,$data){
	return average($p,$p) + c($p,$i,$data);
}
function c($p,$i,$data){
	$divident=$divisor=0.0;
	foreach($data as $q){
		if (empty($q[$i]) || $p===$q){
			continue;
		}
		$simPQ=sim($p,$q);
		$averageQ=average($q,$p);
		$divisor+=abs($simPQ);
		$divident+=(($q[$i]-$averageQ)*$simPQ);
	}
	return ($divident / $divisor);
}
function wrapR($subject, $item, $data){
	return r($data[$subject], $item, $data);
}
function average($set, $controlSet){
	$average=0.0;
	$count=0;
	foreach($set as $i=>$value){
		if (!empty($value) && !empty($controlSet[$i])){
			$average+=$value;
			$count+=1;
		}
	}
	return ($average / $count);
}

#$ratings=array(
#	"alice"=>array(5, 1, 0, 3, 2),
#	"bob"=>array(3, 1, 5, 4, 2),
#	"carol"=>array(4, 0, 5, 0, 3),
#	"chuck"=>array(1, 4, 0, 0, 2),
#	"dave"=>array(0, 4, 3, 0, 1),
#	"eve"=>array(5, 4, 5, 4, 3),
#	"fran"=>array(4, 0, 0, 0,2),
#	"gordon"=>array(3, 4, 0, 5, 1),
#	"isaac"=>array(5, 0, 4, 3, 0),
#	"ivan"=>array(3, 1, 1, 0, 1)
#);
#echo sim($ratings['alice'],$ratings['ivan']);
#echo "<br>";
#echo "<br>";
#echo wrapR('alice', 2, $ratings);
#
#die();



echo "<title>[BETA] prediction</title>";

$date=array(date("Y"),date("n"),date("j"));
#$date=array(2014, 8, 26);
$diffuse=0;

$selectionStart=mktime(0,0,0,$date[1],$date[2],$date[0]);
$selectionEnd=$selectionStart+(60*60*(24+date("I",$selectionStart)));
$rangeSelector=array('time',$selectionStart-$diffuse,$selectionEnd+$diffuse);
$data= $db->selectRange('temp2','*',$rangeSelector);
foreach($data as $record){
	$tupel=$record;
}
$data=null;

#var_dump($tupel);
#echo date("H:i:s", $tupel['time']);
$month=date('n', $tupel['time']);
$year=date('Y', $tupel['time']);
$day=date('j', $tupel['time']);

$div=getdiv('temp2');
for($i=1; $i<=$day;$i++){
	$selectionStart=mktime(0,0,0,$month,$i,$year);
	$selectionEnd=$selectionStart+(60*60*(24+date("I",$selectionStart)));
	$rangeSelector=array('time',$selectionStart-$diffuse,$selectionEnd+$diffuse);
	$data= $db->selectRange('temp2','*',$rangeSelector);
	$sumary[]=prepareData($data,$div,$selectionStart,$selectionEnd);
}

#foreach($sumary as $key=>$daily){
#	echo "<tr><td>Day: ".($key+1)."</td>";
#	foreach ($daily as $key=>$hour){
#		echo "<td>".$key."-".$hour." ";
#	}
#	echo "</tr>";
#}
echo "prediction for ".date("d.m.Y");
echo "<table border='border-collapse'>";
echo "<tr><th>hour</th> <th>prediction</th> <th>difference</th><th>actual measurement</th></tr>";
for ($i=0;$i<25;$i++){
	$r=wrapR(25,$i,$sumary);
	$r=round($r,2);
	echo "<tr><td>".$i."</td><td>".$r."</td><td>".($r-$sumary[25][$i+1])."</td><td>".$sumary[25][$i+1]."</td></tr>";
}
echo "</table>";


#for($i=0;$i<=24;$i++){
#	$hours[]=$i*60*60;
#}
#var_dump($hours);
#var_dump(array($month,$year,$day));
$runtime=microtime(true)-$start;
echo "<div style='position:fixed;bottom:20px;right:50px;' >Runtime: ".$runtime." s</div>";
$db->close();
die();

