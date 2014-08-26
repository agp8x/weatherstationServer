<?php

include('lib/Grouplens.php');
$start=microtime(true);

echo "<title>[BETA] prediction</title>";

$date=array(date("Y"),date("n"),date("j"));
$diffuse=0;

$selectionStart=mktime(0,0,0,$date[1],$date[2],$date[0]);
$selectionEnd=$selectionStart+(60*60*(24+date("I",$selectionStart)));
$rangeSelector=array('time',$selectionStart-$diffuse,$selectionEnd+$diffuse);
$data= $db->selectRange('temp2','*',$rangeSelector);
foreach($data as $record){
	$tupel=$record;
}
$data=null;

$month=date('n', $tupel['time']);
$year=date('Y', $tupel['time']);
$day=date('j', $tupel['time']);

#$div=getdiv('temp');
$div=1;
for($i=1; $i<=$day;$i++){
	$selectionStart=mktime(0,0,0,$month,$i,$year);
	$selectionEnd=$selectionStart+(60*60*(24+date("I",$selectionStart)));
	$rangeSelector=array('time',$selectionStart-$diffuse,$selectionEnd+$diffuse);
	$data= $db->selectRange('temp2','*',$rangeSelector);
	$sumary[]=prepareData($data,$div,$selectionStart,$selectionEnd);
}

$gl=new Grouplens();
$div=getdiv('temp');
echo "prediction for ".date("d.m.Y");
echo "<table border='border-collapse'>\n";
echo "<tr><th>hour</th> <th>prediction</th> <th>difference</th><th>actual measurement</th></tr>\n";
for ($i=0;$i<25;$i++){
	$r=$gl->wrapR($day-1,$i,$sumary);
	$r=$r/$div;
	$r=round($r,2);
	$a=$sumary[$day-1][$i+1]/$div;
	$a=round($a,2);
	echo "<tr><td>".$i."</td><td>".$r."</td><td>".round($r-$a,2)."</td><td>".$a."</td></tr>\n";
}
echo "</table>\n";


$runtime=microtime(true)-$start;
echo "<div style='position:fixed;bottom:20px;right:50px;' >Runtime: ".$runtime." s</div>";
$db->close();
die();

