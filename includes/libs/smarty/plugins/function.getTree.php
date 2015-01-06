<?php

function smarty_function_getTree($params, &$smarty)
{

    /*if (empty($params["tree"]))
    {
        $smarty->_trigger_fatal_error("[plugin] parameter 'tree' cannot be empty");
        return;
    }*/

    $content = "<div class='tree'>".getTreeHtml($params["tree"], $params["path"])."</div>";

    if (!empty($params['assign'])) {
        $smarty->assign($params['assign'],$content);
    } else {
        return $content;
    }

}


function getTreeHtml($tree, $path = array(), $display = true)
{
	
    $html = "";
    if (is_array($tree))
    {
        $html = '<ul';
        if (!$display) $html .= ' style="display:none";';
        $html .= '>';
        foreach($tree as $key=>$val)
        {
            $html .= '<li>';
            if (!empty($val["children"]))
            {
                $html .= "<a href='".$val["link"]."' class='is_dir'>".$val["name"]."</a>";
                $displayChildren = is_array($path) ? in_array($val["id"], $path) : false;
                $html .= getTreeHtml($val["children"], $path, $displayChildren);
            }
            else
            {
                $html .= "<a href='".$val["link"]."' class='is_file'>".$val["name"]."</a>";
            }
            $html .= "</li>";
        }

        $html .= "</ul>";
    }
    return $html;
}