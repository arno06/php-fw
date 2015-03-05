<?php
use core\application\rewriteurl\RewriteURLHandler;
function smarty_function_sanitize_to_url($params, &$smarty)
{
	extract($params);
	if(!$texte)
		return;
	return RewriteURLHandler::sanitize($texte);
}