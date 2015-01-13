<?php
use core\application\Core;
use core\application\Dictionary;

function smarty_function_getPagination($params, &$smarty)
{
	$test = false;
	$info = array();
    $noPage = 0;
	extract($params);
    if(empty($info)||$info["nbPages"]<2)
		return;
    if (isset($info["noPage"])) $noPage = $info["noPage"];
	if(isset($_GET["page"]))
		$test = true;
	$_GET["page"] = $info["currentPage"]-1;

	echo '<div class="pagination"><div class="previous">';
    echo '<a href="'.Core::rewriteURL(Core::$controller,Core::$action,$_GET).'" class="button '.(($info["currentPage"]==1)?'disabled':'').'">'.Dictionary::term("global.pagination.previous").'</a> ';
	echo '</div><div class="pages">';
    if ($noPage)
    {
        echo $info["currentPage"]." / ".$info["nbPages"];
    }
    else
    {
        for($i = 1; $i<=$info["nbPages"]; ++$i)
        {
            if($i==1||$i==$info["currentPage"]||$i==($info["currentPage"]+1)||$i==($info["currentPage"]-1)||$i==$info["nbPages"])
            {
                $_GET["page"] = $i;
                if($i>1)
                    echo ' - ';
                if($i==$info["currentPage"])
                    echo '<span class="current_page">'.$i.'</span>';
                else
                    echo '<a href="'.Core::rewriteURL(Core::$controller, Core::$action, $_GET).'" class="button">'.$i.'</a>';
            }
            if(($i == $info["currentPage"]+2 || $i == $info["currentPage"]-2)&&($i!=1&&$i!=$info["nbPages"]))
                echo " - ... ";
        }
    }
	echo '</div>';
	$_GET["page"] = $info["currentPage"]+1;
	echo '<div class="next">';
    echo '<a href="'.Core::rewriteURL(Core::$controller,Core::$action,$_GET).'" class="button '.(($info["currentPage"]==$info["nbPages"]||!$info["nbPages"])?'disabled':'').'">'.Dictionary::term("global.pagination.next").'</a>';
	echo "</div></div>";
	if(!$test)
		unset($_GET["page"]);
}