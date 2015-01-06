<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


function smarty_modifier_pmatch($string)
{
    $regexp = '/\.[a-zA-Z0-9\-\_]+$/';
    if(preg_match($regexp, $string))
        return true;
    else
        return false;
}

?>
