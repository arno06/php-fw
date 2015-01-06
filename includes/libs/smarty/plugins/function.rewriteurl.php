<?php
use core\application\Core;

function smarty_function_rewriteurl($params, &$smarty)
{
	$controller = "";
	$action ="";
	$langue = "";
	extract($params);
	$param = array();
	foreach($params as $name=>$value)
	{
        if($name=="controller"||$name=="action"||$name=="langue")
			continue;
		$param[$name] = $value;
	}
	echo Core::rewriteURL($controller, $action, $param, $langue);
}