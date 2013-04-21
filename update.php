<?php
#v1.3
include('lib/DBLib.php');
include('function.php');
$db=new DBLib();

$db->connect('localhost','temp','temppw','temp');#lokal

$dir=scandir('data');
$temp=$humi=$ambi=$baro=0;
foreach($dir as $file){
	if(validFile($file)){
		file_put_contents('newData',"");
		#echo "parsing file: ".$file."\n";
		importLogfiletoDatabase('data/'.$file);
		//recent records on frontpage:
		if($file[0].$file[1].$file[2].$file[3].$file[4] == "temp2"){
			$temp=fileToLastSet($file);
		}elseif($file[0].$file[1].$file[2].$file[3].$file[4] == "humi1"){
			$humi=fileToLastSet($file);
		}elseif($file[0].$file[1].$file[2].$file[3].$file[4] == "ambi1"){
			$ambi=fileToLastSet($file);
		}elseif($file[0].$file[1].$file[2].$file[3].$file[4] == "baro1"){
			$baro=fileToLastSet($file);
		}
		///
		unlink('data/'.$file);
	}else{
		#echo "invalidFile: ".$file." \n";
	}
}
$db->close();
file_put_contents('recent',"Temperatur: ".($temp/100)." C<br>Luftfeuchtigkeit: ".($humi/10)." %<br>Helligkeit: ".($ambi/100)." Lux<br>Luftdruck: ".($baro/1000)."mbar");

