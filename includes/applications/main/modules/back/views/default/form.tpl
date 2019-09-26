{include file="includes/head.tpl"}
<h1>{$content.h1}</h1>
<div class="back">
    <a href="{$controller}/" class="button {if null==$content.actions.listing}disabled{/if}">Retour à la liste</a>
</div>
{if $content.error != null}
    <div class="error">
        {$content.error}
    </div>
{/if}
{if $content.confirmation != null}
	<div class="confirmation">
		{$content.confirmation}
	</div>
{/if}
<div class="details form">
	{if $global.get.id !== null}
		{form.instance->display id=$global.get.id}
	{else}
		{form.instance->display}
	{/if}
</div>
{include file="includes/footer.tpl"}