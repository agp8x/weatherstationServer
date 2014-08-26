<?php
# Klimadiagramm (wie Beispielsweise in: http://commons.wikimedia.org/wiki/File:Klimadiagramm_darwin.jpg )

class ClimaDiagram{
	const version="0.1";

	private $months=array('J','F','M','A','M','J','J','A','S','O','N','D');

	private $modes=array('year'=>array(1, 600),'month'=>array(2.6, 1337),'day'=>array(2, 1050));
	private $mode='year';
	private $languages=array('de');
	private $language='de';

	private $temperatureData=array();
	private $rainData=array();
	private $locationName="";
	private $locationCountry="";
	private $locationHeight="";
	private $locationCoordinates="";
	private $hemisphere='north';
	private $result="";

	private function drawBase(){
		foreach ($this->modes as $mode => $prop) {
			if ($this->mode==$mode) {
				$xOffset=$prop[0];
				$width=$prop[1];
			}
		}
		$base='<rect x="0" y="0" width="'.$width.'" height="750" id="background" />
		<text x="15" y="15">'.$this->locationName.','.$this->locationCountry.' ('.$this->locationHeight.' m)</text>
		<text x="450" y="15">'.$this->locationCoordinates.'</text>
		<rect x="40" y="90" width="'.(480*$xOffset).'" height="630" id="border" />
		<text x="18" y="85">T in C</text>
		<text x="'.(20+480*$xOffset).'" y="85">N in mm</text>'."\n";
		$horizontalLines="";
		$rainIndex=400; // Text Höchster Niederschlag
		$rainMarker=0; 
		$temperatureMarker=240;
		$temperatureIndex=50; // Text Höchste Temperatur
		for ($i=90+30; $i < 740; $i+=30) { 
			$horizontalLines.='<line x1="40" y1="'.$i.'" x2="'.(40+480*$xOffset).'" y2="'.$i.'" class="horizontalLine '.(($i==540? 'zeroLine' : '')).'" />'."\n";
			if ($i>=$temperatureMarker) {
				$horizontalLines.='<text x="15" y="'.($i+3).'">'.$temperatureIndex.'</text>'."\n";
				$temperatureMarker=$i+60;
				$temperatureIndex-=10;
			}
			if ($i>=$rainMarker && $rainIndex>=0) {
				$horizontalLines.='<text x="'.(45+480*$xOffset).'" y="'.($i+3).'">'.$rainIndex.'</text>'."\n";
				if ($i<180) {
					$rainMarker=$i+30;
					$rainIndex-=100;
				} elseif ($i<240) {
					$rainMarker=$i+30;
					$rainIndex-=50;
				}else {
					$rainMarker=$i+60;
					$rainIndex-=20;
				}
			}
		}
		$base.=$horizontalLines;
		$horizontalLines=null;
		if ($this->mode=='year'){
			$base.=$this->yearDiagram()."\n";
		}elseif ($this->mode=='day') {
			$base.=$this->dayDiagram()."\n";
		}else{
			$base.='<text x="200" y="200">TODO: modes</text>'."\n";
		}
		$this->result=$base;
		$base=null;
	}

	private function yearDiagram(){
		$verticalLines="";
		foreach ($this->months as $index => $month) {
			$verticalLines.='<line x1="'.(80+$index*40).'" y1="90" x2="'.(80+$index*40).'" y2="720" class="verticalLine" />'."\n";
			$verticalLines.='<text x="'.(60+$index*40).'" y="740">'.$month.'</text>'."\n";
		}
		$verticalLines.=$this->drawRain();
		$verticalLines.=$this->drawTemperature();
		return $verticalLines;
	}

	private function dayDiagram(){
		$verticalLines="";
		for($i=1;$i<=24;$i++) {
			$verticalLines.='<line x1="'.(40+$i*40).'" y1="90" x2="'.(40+$i*40).'" y2="720" class="verticalLine" />'."\n";
			$verticalLines.='<text x="'.(10+$i*40).'" y="740">'.$i.'</text>'."\n";
		}
		$verticalLines.=$this->drawRain();
		$verticalLines.=$this->drawTemperature();
		return $verticalLines;
	}

	private function drawTemperature(){
		$zero=540;
		$max=240;
		$difference=50;
		$resolution=($zero-$max)/$difference;
		$startX=40;
		$path='';
		$lastX=0;
		$lastY=0;
		$i=0;
		foreach ($this->temperatureData as $index=>$value) {
			$x=20+$startX*($i+1);
			$y=$zero+$resolution*-$value;
			if (empty($path)) {
				$path="M ".($x-20).' '.$y.' L '.$x.' '.$y.' ';
			}else{
				$path.=$x.' '.$y.' ';
			}
			$lastX=$x;
			$lastY=$y;
			$i++;
		}
		$path.=($lastX+20).' '.$lastY;
		return '<path d="'.$path.'" id="temperatureChart"/>';
	}
	
	private function drawRain(){
		$rain="";
		$startX=40;
		$startY=540;
		foreach ($this->rainData as $index => $value) {
			if ($value==0) {
				continue;
			}
			if($value>200){
				$localvalue=$value-200;
				$value=200;
				$zero=180;
				$max=90;
				$difference=300;
				$resolution=($max-$zero)/$difference;
				$height=($localvalue*$resolution);
				$rain.='<rect x="'.($startX*($index+1)).'" y="'.($zero+$height).'" width="'.$startX.'" height="'. ($height*-1).'" class="rainChart rainScaleTiny" />'."\n";
			}if ($value>100) {
				$localvalue=$value-100;
				$value=100;
				$zero=240;
				$max=180;
				$difference=100;
				$resolution=($max-$zero)/$difference;
				$height=($localvalue*$resolution);
				$rain.='<rect x="'.($startX*($index+1)).'" y="'.($zero+$height).'" width="'.$startX.'" height="'. ($height*-1).'" class="rainChart rainScaleSmall" />'."\n";
			}
			if($value<=100){
				$zero=540;
				$max=240;
				$difference=100;
				$resolution=($max-$zero)/$difference;
				$height=($value*$resolution);
				$rain.='<rect x="'.($startX*($index+1)).'" y="'.($zero+$height).'" width="'.$startX.'" height="'. ($height*-1).'" class="rainChart rainScaleNormal" />'."\n";
			}
		}
		return $rain;
	}

	public function getGraph(){
		foreach ($this->modes as $mode => $prop) {
			if ($this->mode==$mode) {
				$width=$prop[1];
			}
		}
		$this->drawBase();
		return '<?xml version="1.0" encoding="UTF-8"?>'."\n".
		'<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">'."\n".
			'<svg xmlns="http://www.w3.org/2000/svg" version="1.1" baseProfile="full" width="'.$width.'px" height="750px">'."\n". 
			'<style type="text/css">
				#background{
					fill: white;
					fill: lightgreen;
				}
				#border{
					fill:none;
					stroke:black;
					stroke-width:3px;
				}
				.horizontalLine, .verticalLine{
					stroke:black;
					stroke-width:1px;
				}
				.zeroLine{
					stroke-width:2px;
				}
				#temperatureChart{
					stroke:red;
					stroke-width:2px;
					fill:none;
				}
				.rainScaleNormal{
					fill:lightblue;
				}
				.rainScaleSmall{
					fill:blue;
				}
				.rainScaleTiny{
					fill:darkblue;
				}
				.rainChart{
					stroke:black;
				}
			</style>'."\n".
			$this->result."\n".
			'</svg>';
	}

	public function setLocation($city,$country,$height,$coordinates,$hemisphere='north'){
		$this->locationName=$city;
		$this->locationCountry=$country;
		$this->locationHeight=$height;
		$this->locationCoordinates=$coordinates;
		$this->hemisphere=$hemisphere;
	}
	public function setTemperatureData($data){
		if (is_array($data) && !empty($data)) {
			$this->temperatureData=$data;
		}
	} 
	public function setRainData($data){
		if (is_array($data) && !empty($data)) {
			$this->rainData=$data;
		}
	}
	public function setMode($mode){
		if (array_key_exists($mode, $this->modes)) {
			$this->mode=$mode;
			return true;
			echo "TRUE";
		}
		return false;
	}

}

################
## TESTING    ##
################

#$test=new ClimaDiagram();
#$test->setLocation('Ort','Land','100','50`, 50`' ,'north');
#// $temp=array(25,30,40,-10,5,6.75,50,20,-5,-15.9,-12,-8);
#// $rain=array(1,5,17,48,100,155,425,320,280,76,8,4);
#$temp=array(25,30,40,-10,5,6.75,50,20,-5,-15.9,-12,-8,25,30,40,-10,5,6.75,50,20,-5,-15.9,-12,-8);
#$rain=array(1,5,17,48,100,155,425,320,280,76,8,4,1,5,17,48,100,155,425,320,280,76,8,4);
#$test->setMode('day');
#// $test->setMode('month');
#$test->setRainData($rain);
#$test->setTemperatureData($temp);
#echo $test->getGraph();
?>
