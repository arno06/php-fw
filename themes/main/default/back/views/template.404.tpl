<?xml version="1.0" encoding="iso-8859-1"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="fr" xml:lang="fr">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset={$configuration.site_encoding}" />
    <base href="{$configuration.server_url}{$configuration.site_backoffice}/"/>
    <title>{$head.title}</title>
    <meta name="description" content="{$head.description}"/>
    <link type="text/css" rel="stylesheet" href="{$path_to_theme}/css/style.css"/>
    {foreach from="$styles" item=style}
        <link type="text/css" rel="stylesheet" href="{$style}"/>
    {/foreach}
    {foreach from="$scripts" item=script}
        <script type="text/javascript" src="{$script}"></script>
    {/foreach}
    <script>
        if (typeof jQuery != "undefined")
            jQuery.noConflict();
    </script>
</head>
<body>

{include file="includes/template.menu.tpl"}

<div id="content">

<h1>BO Page introuvable !</h1>

{include file="includes/template.footer.tpl"}