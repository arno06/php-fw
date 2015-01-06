<?php
function smarty_modifier_activeInactive($pString)
{
	return (eregi($GLOBALS["page"],$pString)||($pString=="?page=forum"&&$GLOBALS['page']=="topic"))?"actif":"inactif";
}
?>