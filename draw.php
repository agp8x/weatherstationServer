<?php
#v1.6
function generateChart($today,$mode,$hours=0,$dateInput){
	#$mode:
	#	1:chart for a day, db
	#	2:last $hours hours
	#	3:month
	global $html;
	#global $today;
	global $type;
	global $day;
	global $month;
	global $year;
	
	$diffuse=0; # 10min -+ start/end time
	
	$types=array('ambi1','ambi2','humi1','temp1','temp2','baro1');
	$dummy=array(array(),array());
	
	$db=new DBLib('localhost','temp','temppw','temp');
	$stats="";
	$selectionStart=$selectionEnd=0;
	$chartname="";
	$day=$month=$year=$date=0;
	$chartdistance=60;
	if($mode==1 || $mode==3){
		$day=prependZero($dateInput[2]);
		$month=prependZero($dateInput[1]);
		$year=$dateInput[0];
		$date=$day.".".$month.".".$year;
	}
	if($mode==1){
		//one day 0-24
		$selectionStart=mktime(0,0,0,$dateInput[1],$dateInput[2],$dateInput[0]);
		$selectionEnd=$selectionStart+(60*60*(24+date("I",$selectionStart)));
		$chartname=$type."_".$date;
	}elseif($mode==2){
		//last X hours
		$selectionEnd=time();
		$selectionStart=$selectionEnd-(60*60*$hours);
		$html.="Letzte ".$hours." Stunden von: ".date("H:i d.m.Y",$selectionStart)." bis: ".date("H:i d.m.Y",$selectionEnd);
		$chartname=$type."_last-".$hours;
		$date=date("d.m.Y");
	}elseif($mode==3){
		$selectionStart=mktime(0,0,0,$dateInput[1],1,$dateInput[0]);
		$days=date("t",$selectionStart);
		$selectionEnd=$selectionStart+($days*24*60*60);
		$chartname="month_".$type."_".$month.".".$year;
		$chartdistance=60*24;
	}else{
		return;
	}
	if($mode!=3){
		$rangeSelector=array('time',$selectionStart-$diffuse,$selectionEnd+$diffuse);
		$datas=array();
		for($i=1;$i<=typeToSensorCount($type);$i++){
			$datas[]=$db->selectRange($type.$i,'*',$rangeSelector);
			if($datas[$i-1]===false){
				$html.='No values ('.$type.$i.')';
				/* TODO: think about*/
				if($type!="ambi"){
					return;			
				}
			}
		}
	}else{
		$where=array('year'=>$year,'month'=>$month);
		$db->toggleDevmode();
		$summary=$db->select('summary','*',$where);
		if($summary===false){
			$html.="No values (".$type.")";
			return;
		}
		$datas=array();
//		$labels=array();
		
		$unit=getUnit($type);
		$data=array();
		$minmax=(isset($_GET['minmax']))? true:false;
		for($i=1;$i<=typeToSensorCount($type);$i++){
			$name=$type.$i;
			$min=array(1000000,0);
			$max=array(-1000000,0);
			$avgsum=0;
			$size=0;
			$count=0;
			$data_tmp=array();
			$data_tmp1=array();
			$data_tmp2=array();
			foreach($summary as $daysum){
				$mintmp= (str_replace($unit,'',$daysum[$name.'-min']));
				if($min[0]>$mintmp){
					$min[0]=$mintmp;
					$min[1]=str_replace($unit,'',$daysum[$name.'-min-time']);
				}
				$maxtmp=str_replace($unit,'',$daysum[$name.'-max']);
				if($max[0]<$maxtmp){
					$max[0]=$maxtmp;
					$max[1]=str_replace($unit,'',$daysum[$name.'-max-time']);
				}
				$size+=str_replace($unit,'',$daysum[$name.'-size']);
				$tmp=str_replace($unit,'',$daysum[$name.'-avg']);
				$avgsum+=$tmp;
				$tmp2=str_replace($unit,'',$daysum[$name.'-max-time']);
				$data_tmp[]=array('value'=>$tmp,'time'=>$tmp2);
				if($minmax){
					$data_tmp1[]=array('value'=>$mintmp,'time'=>$tmp2);
					$data_tmp2[]=array('value'=>$maxtmp,'time'=>$tmp2);
				}
				$count++;
			}
			$avg=($count==0)?0:round($avgsum/$count,2);
			$data[]=array('min'=>array($min[0].$unit,$min[1]),
				'max'=>array($max[0].$unit,$max[1]),
				'avg'=>$avg.$unit,
				'size'=>$size);
			$datas[]=$data_tmp;
			if($minmax && ($i>1 || typeToSensorCount($type)==1)){
				$datas[]=$data_tmp1;
				$datas[]=$data_tmp2;
			}
		}
	}
	
	$div=getdiv($type);
	$forceRedraw=false;
	/*wip*/
	if($mode==1 && !($today['match'] && $day==$today[0]) ){
		//if $date is not today =>past, read, (maybe) generate summary for DB
		$date_array=array('year'=>$year,'month'=>$month,'day'=>$day);
		
		$summary=$db->select('summary','*',$date_array,'ORDER BY id DESC');
		$recentSummary=true;
		foreach($types as $tmp){
			if($tmp==$types[1]){
				continue;
			}
			$recentSummary=($summary[0][$tmp.'-min-time'] > 0) && $recentSummary;
		}
		if($summary===false ||isset($_GET['totalforce']) || ! $recentSummary){
		//stats are not in db, generate new, also redraw graph
			$forceRedraw=true;
			$db->delete('summary',$date_array);//avoid multiple (outdated) entries
			$stats=$date_array;
			
			foreach($types as $tmp){
				//get values
				${$tmp}=$db->selectRange($tmp,'*',$rangeSelector,'ORDER BY time');
				if(${$tmp}===false){
					//check if empty, maybe return is too harsh (ambi2 might be dark all day.)
					#return;
					${$tmp}=array(0,0);
				}
				//magic happens here
				$stats+=dataStat(${$tmp},$tmp,true);
			}
			
			$stats+=array('time'=>time());
			if($db->insert('summary',$stats)){
				//insert summary into DB, get summary_id
				$id=$db->select('summary','id',array('day'=>$day,'month'=>$month,'year'=>$year),'ORDER BY id DESC');
				if($id===false){
					//something went terribly wrong
					echo "FAILURE";
					return;
				}
				$id=$id[0]['id'];
				foreach($types as $tmp){
					//default of 9 values all over the day displayed below the chart
					$outlined=outlinedLogPoints(${$tmp});
					foreach($outlined as $set){
						$db->insert('outlined',array('summary_id'=>$id,'type'=>$tmp,'time'=>$set[1],'value'=>$set[0]));
					}
				}
			}
			//get fresh summary from DB
			$summary=$db->select('summary','*',$date_array,'ORDER BY id DESC');
		}/*else{*/
		//create summary
		$outlined=array();
		$data=array();
		for($i=1;$i<=typeToSensorCount($type);$i++){
			//collect data for each sensor of this $type
			$outlinedData=$db->select('outlined','*',array('summary_id'=>$summary[0]['id'],'type'=>$type.$i),'ORDER BY time');
			if(!$outlinedData===false){
				$outlinedSet=array();
				//transform
				foreach($outlinedData as $set){
					$outlinedSet[]=array($set['value'],$set['time']);
				}
				$outlined[]=$outlinedSet;
				$outlinedSet=null;
			}
			$data[]=array('min'=>array($summary[0][$type.$i.'-min'],$summary[0][$type.$i.'-min-time']),
				'max'=>array($summary[0][$type.$i.'-max'],$summary[0][$type.$i.'-max-time']),
				'avg'=>$summary[0][$type.$i.'-avg'],
				'size'=>$summary[0][$type.$i.'-size']);
		}
		//generate
		$stats=logStats($dummy,$type,$data,$outlined);
		/*}*/
	}elseif($mode!=3){
		//today
		$stats=logStats($datas,$type,false,0,$mode);
	}else{
		$stats=logStats($dummy,$type,$data,$dummy,3);
	}
	/*/wip*/
	######################################
	##	DRAWING							##
	######################################
	$oldDir=getcwd();
	
	//parameters to drawing
	##<logic is busted>
	$force=false;
	$force=(isset($_GET['force']) && $_GET['force']!="false");
	$force=($force || $forceRedraw);
	##</logic is busted>
	//only redraw, if new data was added, indicated by 'newData' token left by update.php
	$newData=false;
	if(file_exists('newData')){
		unlink('newData');
		$newData=true;
	}
	$file_exists=false;
	$svg=false;
	if(isset($_GET['lib'])){
		$svg=true;
		if(file_exists("charts/".$chartname.".svg")){
			$file_exists=true;
		}
	}else{
		if(file_exists("pchart/".$chartname.".png")){
			$file_exists=true;
		}
	}
	//temp-fix
	if((date("d.m.Y")==$date && ($newData || $mode==2)) || !$file_exists || isset($_GET['force']) || $forceRedraw){
	#if(date("d.m.Y")==$date || !$file_exists || isset($_GET['force']) || $forceRedraw){
	#if((date("d.m.Y")==$date &&$force) || !file_exists($chartname) || $force){
	#if(true){#always redraw
		if($svg){
			include('ownlib.php');	
		}else{
			chdir('pchart');
			include("class/pData.class.php");
			include("class/pDraw.class.php");
			include("class/pImage.class.php");
		
			$myData=new pData();
			if(date("I",$selectionStart)==1 ){
				//summertime
				//if($mode!=3){
					$selectionStart+=3600;
				//}
			}
			$labels=getLabels($datas[0],$selectionStart,$selectionEnd,$chartdistance);
			if($mode==3){
				$selectionStart+=60*60*24;
				$div=1;
			}
			foreach($datas as $key=>$dataset){
				$values=prepareData($dataset,$div,$selectionStart,$selectionEnd,$chartdistance);
				//TODO: replace $type.($key+1) with actual name
				$myData->addPoints($values,$type.($key+1));
				#file_put_contents('val1',var_export($values,true));
			}

			$myData->addPoints($labels,"Labels");
			#file_put_contents('label',var_export($labels,true));
			$myData->setAxisName(0,typeToFullName($type).getUnit($type));
			drawChart($myData,$chartname.".png",$date,$type);
		}
	}
	chdir($oldDir);
	$path="pchart/".$chartname.".png";
	if($svg){
		$path="charts/".$chartname.".svg";
	}
	if(isset($_GET['short'])){
		$html.="<a href='".$path."'>Grafik</a>\n";
	}else{
		$html.="<img id='chart' src='".$path."' alt='chart'/>\n";
	}
	//add summary
	$html.=$stats;
}
