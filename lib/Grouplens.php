<?php

class Grouplens{

	public function r($p,$i,$data){
		return $this->average($p,$p) + $this->c($p,$i,$data);
	}
	
	public function wrapR($subject, $item, $data){
		return $this->r($data[$subject], $item, $data);
	}
	
	private function sim($a, $b){
		$averageA=$this->average($a,$b);
		$averageB=$this->average($b,$a);
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
	
	private function c($p,$i,$data){
		$divident=$divisor=0.0;
		foreach($data as $q){
			if (empty($q[$i]) || $p===$q){
				continue;
			}
			$simPQ=$this->sim($p,$q);
			$averageQ=$this->average($q,$p);
			$divisor+=abs($simPQ);
			$divident+=(($q[$i]-$averageQ)*$simPQ);
		}
		return ($divident / $divisor);
	}
	
	private function average($set, $controlSet){
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
