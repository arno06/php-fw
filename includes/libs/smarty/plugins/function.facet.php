<?php

function smarty_function_facet($params, &$smarty)
{

    if (empty($params["data"]))
    {
        $smarty->_trigger_fatal_error("[plugin] parameter 'data' cannot be empty");
        return;
    }

    $content = "<div class='facet'><h4>".$params["data"]["label"]."</h4>".getFacetHtml($params["data"]["values"], $params["data"]["id"])."</div>";

    if (!empty($params['assign'])) {
        $smarty->assign($params['assign'],$content);
    } else {
        return $content;
    }

}


function getFacetHtml($values, $id, $level = 1, $closed = false)
{
	$nbShow = 10;
    $html = "";
    if (is_array($values))
    {
        $html = '<ul';
        if ($level == 1) $html .= ' id="facet_'.$id.'"';
        if ($closed) $html .= ' class="closed"';
        $html .= '>';
        $len = sizeof($values);
        $count = 0;
        foreach($values as $key=>$val)
        {
            $hideChildren = $len > 1 && !$val["checked"];
            $html .= '<li';
            if ($level == 1 && $count >= $nbShow) $html .= ' class="closed"';
            $html .= '><input type="checkbox" value="'.$val["uncheckurl"].'" ';
            if ($val["checked"]) $html .= 'checked="checked"';
            $html .= '><a href="'.$val["url"].'"';
            if ($val["checked"]) $html .= ' class="selected"';
            $html .= '>';
            if ($id == "couleur") $html .= '<span class="color '.$val["label"].'"></span>';
            $html .= $val["label"].'</a>&nbsp;<span>('.$val["count"].')</span>';
            if (isset($val["values"]))
                $html .= getFacetHtml($val["values"], $id, $level+1, $hideChildren);
            $html .= '</li>';
            $count++;
        }

        $html .= "</ul>";
    }
    return $html;
}