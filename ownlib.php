<?php
include('lib/psChart/psChart.php');
$chart=new psChart();
foreach($datas as $key=>$dataset){
	$chart->setData(prepareData($dataset,$div,$selectionStart,$selectionEnd,$chartdistance));
}
$chart->setLabels(getLabels($chart->getFirstDataset(),$selectionStart,$selectionEnd,$chartdistance));
$chart->createSVG("charts/".$chartname.".svg");

#$html.="<embed src='charts/".$chartname.".svg' type='image/svg+xml' />";

