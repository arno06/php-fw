{if !$request_async}{include file="includes/template.head.tpl"}{/if}
<h1>Hello world</h1>
{foreach from=$content.test item="bouboup"}
	<div>woot</div>
{foreachelse}
	<div>pas woot</div>
{/foreach}
{if !$request_async}{include file="includes/template.footer.tpl"}{/if}