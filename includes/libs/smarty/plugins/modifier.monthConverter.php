<?php
function smarty_modifier_monthConverter($pString, $pFull = false)
{
	$mois = array("1"=>"Janvier",
				  "2"=>"Février",
				  "3"=>"Mars",
				  "4"=>"Avril",
				  "5"=>"Mai",
				  "6"=>"Juin",
				  "7"=>"Juillet",
				  "8"=>"Ao&ucirc;t",
				  "9"=>"Septembre",
				  "10"=>"Octobre",
				  "11"=>"Novembre",
				  "12"=>"Décembre");
	return $mois[($pString+1)];
}
?>