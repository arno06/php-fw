{include file="includes/template.head.tpl"}
<h1>{$content.h1}</h1>
<div class="back">
    <a href="{$controller}/" class="button {if not isset($content.actions.listing)}disabled{/if}">Retour à la liste</a>
</div>
{if isset($content.error) && !empty($content.error)}
    <div class="error">
        {$content.error}
    </div>
{/if}
{if isset($content.confirmation) && !empty($content.confirmation)}
	<div class="confirmation">
		{$content.confirmation}
	</div>
{/if}
<div class="details form">
	{if isset($smarty.get.id)}
		{form_instance->display id=$smarty.get.id}
	{else}
		{form_instance->display}
	{/if}
</div>
{include file="includes/template.footer.tpl"}