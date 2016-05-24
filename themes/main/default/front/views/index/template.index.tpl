{if !$request_async}{include file="includes/template.head.tpl"}{/if}
<?php print_r(Core); ?>
{*
test
comments
*}
<h1>Hello world</h1>
{foreach from=$content.test item="bouboup"}
	<div>woot</div>
	{foreach from=$bouboup.test item="aze"}
		<div>Sparta</div>
	{/foreach}
	{$content.test}
{foreachelse}
	<div>pas woot</div>
{/foreach}
{$content.some.var|ucfirst|strtolower}
<?php
//test
trace("bouboup");
?>
{* test *}
{$test.foo}
{if !$request_async}{include file="includes/template.footer.tpl"}{/if}