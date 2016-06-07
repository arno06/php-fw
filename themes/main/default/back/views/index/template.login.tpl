{include file="includes/template.head.tpl"}
	<div id="connexion">
        <h1>Identification</h1>
        {if $content.error!=""}
        <div class='error'>{$content.error}</div>
        {/if}
        {form.login->display}
	</div>
{include file="includes/template.footer.tpl"}