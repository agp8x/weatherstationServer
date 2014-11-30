<?php
#v1.6

#valid types:temp,humi,ambi,baro

function importLogfileToDatabase($fileImport){
	global $db;
	$filename=explode("/",$fileImport);
	if(sizeof($filename)<2){
		echo "INVALID $fileImport\n";
		die();
	}
	if(!validFile($filename[1])){
		return;
	}
	$logtype=explode("_",$filename[1]);
	if(sizeof($logtype)<2){
		echo "INVALID $fileImport\n";
		die();
	}
	$fileDate=DateTime::createFromFormat("m.d.Y",$logtype[1])->getTimestamp();
	$startTime=mktime(0,0,0,date("n",$fileDate),date("j",$fileDate),date("Y",$fileDate));
	$endTime=$startTime+(60*60*24);
	$existingData=$db->selectRange($logtype[0],'*',array('time',$startTime,$endTime));
	$emptyDB=false;
	if($existingData===false){
		$emptyDB=true;
	}
	$file=file($fileImport);

	foreach($file as $line){
		$line=explode(";",$line);
		
		if($emptyDB){
			$db->insert($logtype[0],array('time'=>$line[1],'value'=>$line[0]));
		}else{
			if(!isset($exitingData[$line[1]])){
				$db->insert($logtype[0],array('time'=>$line[1],'value'=>$line[0]));
			}
		}
	}
	
}
function validFile($filename){
	$humi=(strpos($filename,'humi')===false);
	$ambi=(strpos($filename,'ambi')===false);
	$temp=(strpos($filename,'temp')===false);
	$baro=(strpos($filename,'baro')===false);
	if(!$temp || !$humi || !$ambi || !$baro){
		return true;
	}
		return false;
}

function logStats($datas,$type,$alt=false,$outlineAlt=0,$mode=1){
	$output="";
	$left=10;
	$max=getMax($type);
	for($num=0;$num<=$max;$num++){
		$printValues=true;
		if($num>0){
			$left+=300;
		}
		$div=getdiv($type);
		$unit=getUnit($type);
		$stat=$timePoints=array();
		if($alt===false){
			$stat=dataStat($datas[$num],$type);
			$timePoints=outLinedLogPoints($datas[$num]);
		}else{
			$stat=$alt[$num];
			$timePoints=$outlineAlt[$num];
			$printValues = $stat['min'][1]!=0;
		}
		$output.="<div style='position:absolute;left:".$left."px;'>\n";
		$output.=$type.($num+1)."<br>\n";
		if($printValues){
			$output.="minimum: ".$stat['min'][0]." @ ".ttdls((int) $stat['min'][1],$mode)."<br>\n";
			$output.="maximum: ".$stat['max'][0]." @ ".ttdls((int) $stat['max'][1],$mode)."<br>\n";
			$output.="average: ".$stat['avg']." @ ".ttdls2($stat['min'][1],$mode)."<br>\n";
			$output.="Logpoints: ".$stat['size']."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".ttdls3((int) $stat['min'][1],$mode);
			$output.="<br><br><br>";
			if($mode==1){
				foreach($timePoints as $point){
					$output.=date("H:i:s",(int) $point[1])." - ".$point[0]/$div.$unit."<br>\n";
				}
			}
		}else{
			$output.="Keine Daten</div>";
		}
		$output.="</div>\n";
	}
	return $output;
}
function ttdls($time,$mode){
	if($mode==1){
		return date("H:i:s",$time);
	}
	return date("d.m. H:i",$time);
}
function ttdls2($time,$mode){
	if($mode==1){
		return date("d.m.Y",$time);
	}
	return date("m.Y",$time);
}
function ttdls3($time,$mode){
	if($mode==1){
		return date("l",$time);
	}
	return date("F",$time);
}
function getDiv($type){
	switch($type){
		case 'temp':
			return 100;
		case 'ambi':
		case 'humi':
			return 10;
		case 'baro':
			return 1000;
	}
	return 1;
}
function getUnit($type){
	switch($type){
		case 'temp':
			return " C";
		case 'ambi':
			return " Lux";
		case 'humi':
			return " %RH";
		case 'baro':
			return " mbar";
	}
	return "";
}
function getMax($type){
	switch($type){
		case 'temp':
		case 'ambi':
			return 1;
		case 'humi':
		case 'baro':
			return 0;
	}
	return 0;
}
function dataStat($data,$type,$typenames=false){
	$name=$type;
	$type=($typenames)? substr($type,0,-1) : $type;
	$div=getdiv($type);
	$unit=getUnit($type);
	$max=-10000000;
	$maxAt=0;
	$min=10000000;
	$minAt=0;
	$sum=0;
	foreach($data as $set){
		$sum+=$set['value'];
		if($set['value']>$max){
			$max=$set['value'];
			$maxAt=$set['time'];
		}
		if($set['value']<$min){
			$min=$set['value'];
			$minAt=$set['time'];
		}
	}
	$min=$min/$div . $unit;
	$avg=$sum/(sizeof($data));
	$avg=round($avg/$div,2).$unit;
	$max=$max/$div.$unit;
	if($typenames){
		return array($name.'-max'=>$max,$name.'-max-time'=>$maxAt,$name.'-min'=>$min,$name.'-min-time'=>$minAt,$name.'-avg'=>$avg,$name.'-size'=>sizeof($data));
	}else{
		return array('max'=>array($max,$maxAt),'min'=>array($min,$minAt),'avg'=>$avg,'size'=>sizeof($data));
	}
}
function outlinedLogPoints($data,$interval=180,$start=0,$end=0){
	$stamps=outlinedTimeStamps($interval,$start,$end);
	$timePoints=array();
	$last=0;
	foreach($stamps as $stamp){
		$timePoints[]=getNearest($data,$stamp,$last);
		$last=$stamp;
	}
	return $timePoints;
}
function outlinedTimeStamps($interval=180,$start=0,$end=0){
	$base=getDay();
	$return=array();
	$minute=0;
	if($start==0 && $end==0){
		for($i=0;$minute<=24*60;$i++){
			$return[]=mktime(0,$minute,0,$base[0],$base[1],$base[2]);
			$minute+=$interval;
		}
	}else{
		$second=$start;
		$second-=943916400;
		$end=$end-943916400;
		for($i=0;$second<=$end;$i++){
			$return[]=mktime(0,0,$second,0,0,0);
			$second+=$interval*60;
		}
	}
	return $return;
}
function getDay(){
	#return array($_GET['month'],$_GET['day'],$_GET['year']);
	global $day;
	global $month;
	global $year;
	return array($month,$day,$year);
}
function getNearest($data,$time,$last=-1){ 
	if(!is_array($data)){
		return array(0,$time);
	}
	if($last==-1 || isset($_GET['abs'])){
		$lastValue=0;
		$lastTime=0;
		foreach($data as $set){
			if($set['time'] <=$time or (abs($set['time']-$time)<abs($lastTime-$time))){
				$lastValue=$set['value'];
				$lastTime=$set['time'];
			}
			if($lastValue==0){
				$lastValue=$set['value'];
				$lastTime=$set['time'];
			}
		}
		return array($lastValue,$lastTime);
	}/*else*/
	$sum=0;
	$count=0;
	foreach($data as $set){
		if($set['time'] <=$time && $set['time']>=$last){
				$sum+=$set['value'];
				$count++;
		}
		if($count==0 && $set['time']>=$time){
			$sum=$set['value'];
			$count++;
		}
	}
	return array(($count==0)?0:round($sum/$count,1),$time);
}
function prepareData($data,$div,$start=0,$end=0,$chartdistance=60){
	$values=array();
	$rawdata=outlinedLogPoints($data,$chartdistance,$start,$end);
	foreach($rawdata as $set){
		$values[]=round($set[0]/$div,1);
	}
	return $values;
}
function getLabels($data,$start,$end,$chartdistance=60){
	$rawdata=outlinedtimeStamps($chartdistance,$start,$end);
	$labels=array();
	foreach($rawdata as $set){
		if($chartdistance==60){
			$labels[]=date("G",$set);
		}else{
			$labels[]=date("d",$set);
		}
	}
	return $labels;
}
function typeTofullName($type){
	switch($type){
		case 'temp':
			return "Temperatur";
		case 'humi':
			return "Luftfeuchtigkeit";
		case 'ambi':
			return "Umgebungshelligkeit";
		case 'baro':
			return "Luftdruck";
	}
	return null;
}

function drawChart($myData,$target,$date,$type){
	$width=1200;
	$height=600;
	$fontsize=12;
	
	$myData->setSerieDescription("Labels","Stunde");
	$myData->setAbscissa("Labels"); 
	$myPicture = new pImage($width,$height,$myData);
	#$myPicture->Antialias = FALSE;#antialiasing off
	$Settings = array("R"=>170, "G"=>183, "B"=>87, "Dash"=>1, "DashR"=>190, "DashG"=>203, "DashB"=>107);
	$myPicture->drawFilledRectangle(0,0,$width,$height,$Settings);#background
	/* Overlay with a gradient */
	$Settings = array("StartR"=>219, "StartG"=>231, "StartB"=>139, "EndR"=>1, "EndG"=>138, "EndB"=>68, "Alpha"=>50);
	$myPicture->drawGradientArea(0,0,$width,$height,DIRECTION_VERTICAL,$Settings);
	$myPicture->drawGradientArea(0,0,$width,20,DIRECTION_VERTICAL,array("StartR"=>0,"StartG"=>0,"StartB"=>0,"EndR"=>50,"EndG"=>50,"EndB"=>50,"Alpha"=>80));
	$myPicture->drawRectangle(0,0,$width-1,$height-1,array("R"=>0,"G"=>0,"B"=>0));#border
	/* Write the chart title */ 
	$myPicture->setFontProperties(array("FontName"=>"fonts/Forgotte.ttf","FontSize"=>$fontsize,"R"=>255,"G"=>255,"B"=>255));
	$myPicture->drawText(10,16,"Durchschnittliche ".typeToFullName($type)." @ ".$date,array("FontSize"=>11,"Align"=>TEXT_ALIGN_BOTTOMLEFT));
	$myPicture->drawText($width-200,16,"erzeugt @ ".date("H:i:s d.m.Y"),array("FontSize"=>11,"Align"=>TEXT_ALIGN_BOTTOMLEFT));
	$myPicture->setFontProperties(array("FontName"=>"fonts/pf_arma_five.ttf","FontSize"=>$fontsize,"R"=>0,"G"=>0,"B"=>0));#default font
	$myPicture->setGraphArea(60,40,$width-10,$height-30);#chart area
	$scaleSettings = array("XMargin"=>10,"YMargin"=>10,"Floating"=>TRUE,"GridR"=>200,"GridG"=>200,"GridB"=>200,"DrawSubTicks"=>TRUE,"CycleBackground"=>TRUE);
	$myPicture->drawScale($scaleSettings);#draw scale
	$myPicture->Antialias = TRUE;#antialiasing
	$myPicture->setShadow(TRUE,array("X"=>1,"Y"=>1,"R"=>0,"G"=>0,"B"=>0,"Alpha"=>10));#shadow computing
	/* Draw the line chart */
	$myPicture->drawLineChart();
	$myPicture->drawPlotChart(array("DisplayValues"=>TRUE,"PlotBorder"=>TRUE,"BorderSize"=>2,"Surrounding"=>-60,"BorderAlpha"=>80));
	$myPicture->drawLegend(590,9,array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_HORIZONTAL,"FontR"=>255,"FontG"=>255,"FontB"=>255));#legende
	$myPicture->Render($target);
}

function calendarDay($day,$year,$month,$selection, $future){
	$baselink="&amp;year=".$year."&amp;month=".$month."&amp;day=".$day."'";
	if($future){
		return $day." (H A B)";
	}else{
		return tempLink($baselink,$day,($selection=="temp")).humiLink($baselink,($selection=="humi")).ambiLink($baselink,($selection=="ambi")).baroLink($baselink,($selection=="baro"));
	}
}
function tempLink($baselink,$day,$selected=false){
	$link="<a href='?type=temp".$baselink;
	if($selected){
		$link.=" class='selected'";
	}
	return $link." >".$day."</a>";
}
function ambiLink($baselink,$selected=false){
	$link=" <a href='?type=ambi".$baselink;
	if($selected){
		$link.=" class='selected'";
	}
	return $link." >A</a>";
}
function humiLink($baselink,$selected=false){
	$link=" (<a href='?type=humi".$baselink;
	if($selected){
		$link.=" class='selected'";
	}
	return $link." >H</a>";
}
function baroLink($baselink,$selected=false){
	$link=" <a href='?type=baro".$baselink;
	if($selected){
		$link.=" class='selected'";
	}
	return $link." >B</a>)";
}
function calendarNav($month,$year){
	global $start_year_of_recordings;
	
	$prevMonth=$prevYear=$nextMonth=$nextYear=0;
	if($month==12){
		$prevMonth=$month-1;
		$prevYear=$year;
		$nextMonth=1;
		$nextYear=$year+1;
	}elseif($month==1){
		$prevMonth=12;
		$prevYear=$year-1;
		$nextMonth=$month+1;
		$nextYear=$year;
	}else{
		$prevMonth=$month-1;
		$prevYear=$year;
		$nextMonth=$month+1;
		$nextYear=$year;
	}
	$show['prev']=$prevYear >= $start_year_of_recordings;
	$show['next']=! isFuture(array(date("j"),date("n"),date("Y")), 0, $nextMonth, $nextYear);
	$links="<div id='calendarNav'>\n\t";
	if ($show['prev']){
		$links.="<a href='?mode=month&amp;year=".$prevYear."&amp;month=".$prevMonth."'>&lt;&lt; (".monthToName($prevMonth).")</a>";
	}else{
		$links.=monthToName($prevMonth);
	}
	$links.="&nbsp; <a href='?mode=month&amp;year=".$year."&amp;month=".$month."'>".monthToName($month)."</a> &nbsp; ";
	if ($show['next']){
		$links.="<a href='?mode=month&amp;year=".$nextYear."&amp;month=".$nextMonth."'>&gt;&gt; (".monthToName($nextMonth).")</a>";
	}else{
		$links.=monthToName($nextMonth);
	}
	$links.="\n</div>\n";
	return $links;
}
function monthToName($month){
	$dateObj   = DateTime::createFromFormat('!m', $month);
	$monthName = $dateObj->format('F');
	return $monthName;
}
function prependZero($number){
	return ($number<10)? "0".$number : $number;
}
function drawCalendar($date,$type){
	global $today;
	$days=date("t",$date);
	$month=date("n",$date);
	$year=date("Y",$date);
	$selectedDay=(isset($_GET['day']))? $_GET['day'] : 0;
	$today=array('match'=>($month==date("n") && $year==date("Y")),date("j"),date("n"),date("Y"));
	$first=mktime(0,0,0,$month,1,$year);
	$firstDay=date("w",$first);
	
	$calendar="<div id='calendar'><br/>\n".calendarNav($month,$year);
	$day=1;
	#$day=0;
	$calendar.="<table><tr><th>So</th> <th>Mo</th> <th>Di</th> <th>Mi</th> <th>Do</th> <th>Fr</th> <th>Sa</th></tr>\n";
	for($row=0;$row<6;$row++){
		$calendar.="\t<tr>\n";
		for($col=0;$col<7;$col++){
			$pos=($row*7)+$col;
			if($pos>=$firstDay && $day<=$days){
				if($today['match'] && $day==$today[0]){
					$calendar.="\t\t<td class='today'>";
				}else{
					$calendar.="\t\t<td>";
				}
				$future=isFuture($today, $day, $month, $year);
				$calendar.=calendarDay($day,$year,$month,($day==$selectedDay)? $type : false, $future);
				$day++;
			}else{
				$calendar.="\t\t<td> &nbsp;";
			}
			$calendar.="</td>\n";
		}
		$calendar.="\t</tr>\n";
	}
	$calendar.="</table>
	Letzte: <a href='?mode=last&amp;num=24&amp;type=temp'>24h</a> <a href='?mode=last&amp;num=48&amp;type=temp'>48h</a> <a href='?mode=last&amp;num=96&amp;type=temp'>96h</a>  &nbsp;&nbsp;&nbsp;<a href='?'>Temperatur Heute</a></div>";
	return $calendar;
}
function isFuture($today, $day, $month, $year){
	$dateObj   = DateTime::createFromFormat('!m', $month-1);
	$numDays = $dateObj->format("t");
	# check year
	if ($today[2] < $year){
		return true;
	}
	if ($today[2] == $year){
		# check month
		if ($today[1] < $month){
			# last day of month => enable next month
			if($today[0] == $numDays){
				return $day != 0 && $day != 1;
			}
			return true;
		}
		if($today[1] == $month){
			# enable next day anyways
			if($today[0]+1 == $day){
				return false;
			}
			# check day
			return $today[0] < $day && $overwriteDay;
		}
	}
	# base case
	return false;
}

function typeToSensorCount($type){
	switch($type){
		case "temp":
		case "ambi":
			return 2;
		case "humi":
		case "baro":
			return 1;
	}
}
function fileToLastSet($file){
	$lines=file("data/".$file);
	$last=$lines[sizeof($lines)-2];
	$temp=explode(";",$last);
	return $temp[0];
}

