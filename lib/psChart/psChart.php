<?php
#v0.4

class psChart{
	//TODO!!!
	private $distanceHor;
	private $pixelperunit;
	private $minimum;
	private $datas=array();
	private $labels;
	private $colors=array('blue','yellow','red','white','black');
	//TODO!!!
	public function createSVG($filename){
		$svg='<?xml version="1.0" encoding="UTF-8"?>
	<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">
	 
	<svg xmlns="http://www.w3.org/2000/svg"
	xmlns:xlink="http://www.w3.org/1999/xlink"
	xmlns:ev="http://www.w3.org/2001/xml-events"
	version="1.1"
	baseProfile="full"
	width="1200px"
	height="600px" >
	<style type="text/css" >
		<![CDATA[/*line.entry {stroke: blue;stroke-width: 10px;}line.helpline {stroke: lightgrey;stroke-width: 1px;stroke-dasharray: 2,2;}line.scalemarker{stroke: black;stroke-width: 1px;}text.scaletext{}text.label{}*/
		line,text{stroke:black;fill:black;}
		.red{stroke:red;}
		.black{stroke:black;}
		.indicator {stroke: lightgrey;stroke-width: 1px;stroke-dasharray: 2,2;}
		text.title{
			stroke:white;fill:white;
		}
		#chartArea{stroke:none;fill:#6daa54;}
		]]>
	</style>
	<defs>
		<!--line id="ymarker" x1="58" x2="62" y1="50" y2="50" /-->
	</defs>
	<line x1="70" x2="1180" y1="570" y2="570" />
	<rect x="0" y="0" width="1200" height="22" />
	<text class="title" x="10" y="15">titel</text>
	<text class="title" x="550" y="15">legende</text>
	<text class="title" x="1000" y="15">'.date("H:i:s d.m.Y").'</text>
	<line x1="60" x2="60" y1="50" y2="560" />'."\n";
		$svg.='<rect x="70" y="50" width="1110" height="510" id="chartArea" />'."\n";
		$height=510;
		$width=1110;
		$stats=array();
		foreach($this->datas as $data){
			$stats[]=$this->datastat2($data);
		}
		//max difference of values
		$difference=$this->getMax($stats)-$this->getMin($stats);
		$this->minimum=$this->getMin($stats);
		
		$this->pixelperunit=$height/$difference;
		$mainpoints=$difference*4;
		$unitperpoint=$difference/$mainpoints;
		$distance=$height/($mainpoints-1);
		$label=$this->getMax($stats);
		//draw y-axis
		for($i=0;$i<$mainpoints;$i++){
			$y=50+($i*$distance);
			#$y=$i*11;
			$string="\t".'<line x1="58" y1="'.$this->rnd($y).'" y2="'.$this->rnd($y).'"';
			if($i%2==1){
				$string.=' x2="60.5" class="red"'." />\n";
			}else{
				$string.=' x2="62"'." />\n".'<line x1="70" x2="1180" y1="'.$this->rnd($y).'" y2="'.$this->rnd($y).'" class="indicator" />
			<text x="20" y="'.$this->rnd($y+10).'">'.$label.'</text>'."\n";
			}
			$svg.=$string;
			$label-=$unitperpoint;
		}
		$this->distanceHor=$width/(sizeof($this->labels)-1);
		//draw x-axis
		$labels=$this->labels;
		for($i=0;$i<sizeof($labels);$i++){
			$x=70+($i*$this->distanceHor);
			$string="\t".'<line x1="'.$this->rnd($x).'" x2="'.$this->rnd($x).'" y1="568" y2="573" />';
			$string.="\n\t".'<text x="'.$this->rnd($x-5).'" y="590">'.$labels[$i].'</text>'."\n".'<line x1="'.$this->rnd($x).'" x2="'.$this->rnd($x).'" y1="50" y2="560" class="indicator" />'."\n";
			$svg.= $string;
		}
		foreach($this->datas as $key=>$data1){
			$svg.='<path d="'.$this->pathFromData($data1).'" stroke="'.$this->colors[$key].'" fill="none" />'."\n";
		}
		$svg.='</svg>';#echo $filename;
		file_put_contents($filename,$svg);
	}
	private function datastat2($data){
		$min=5555;
		$max=-5555;
		foreach($data as $value){
			if($value<$min){
				$min=$value;
			}elseif($value>$max){
				$max=$value;
			}
		}
		return array('min'=>round($min,1),'max'=>round($max,1));
	}
	private function pathFromData($data){
		/*$distanceHor=46.25;
		$pixelperunit=47.663551401869;
		$minimum=11.81;*/
		/*$this->distanceHor;
		$this->pixelperunit;
		$this->minimum;*/
		$path="";
		foreach($data as $key=>$value){
			$value=(double) $value;
			$width=$this->rnd($key*$this->distanceHor+70);
			$height=$this->rnd($this->pixelperunit*($value-$this->minimum));
			
			//echo "v: ".$value."\n";
			//echo "\th: ".$height."\n";
			$height=560-$height;
			//echo "\th: ".$height."\n";echo "v:".($value)." - h:".$height." - k:".$key." - w:".$width."\n";
			
			if(empty($path)){
				$path.="M ".$width." ".$height." L ";
			}else{
				$path.="".$width." ".$height." ";
			}
		}
		return $path;
	}
	public function setData($data1,$data2=null){
		$this->datas[]=$data1;
		if($data2!=null){
			$this->datas[]=$data2;
		}
	}
	public function setLabels($labels){
		$this->labels=$labels;
	}
	
	private function rnd($double){
		return round($double,2);
	}
	private function getMin($stats){
		if(isset($stats[0]) && isset($stats[1])){
			return min($stats[0]['min'],$stats[1]['min']);
		}elseif (isset($stats[0])){
			return $stats[0]['min'];
		}
		return 0;
	}
	private function getMax($stats){
		if(isset($stats[0]) && isset($stats[1])){
			return max($stats[0]['max'],$stats[1]['max']);
		}elseif (isset($stats[0])){
			return $stats[0]['max'];
		}
		return 0;
	}
	public function getFirstDataset(){
		if(isset($this->datas[0])){
			return $this->datas[0];
		}
		return null;
	}
	
	
	}//ENDOFCLASS
	
	
	
	/*function customround($number){
		$number=$number-floor($number);
		if($number*/
	/*$distance=$height/((24*2)-2);
	for($i=0;$i<(24*2)-1;$i++){
		$y=50+($i*$distance);
		#$y=$i*11;
		$string="\t".'<line x1="58" y1="'.$y.'" y2="'.$y.'"';
		if($i%2==1){
			$string.=' x2="60.5" class="red"'." />\n";
		}else{
			$string.=' x2="62"'." />\n".'<line x1="70" x2="1180" y1="'.$y.'" y2="'.$y.'" class="indicator" />'."\n";
		}
		echo $string;
	}*/
