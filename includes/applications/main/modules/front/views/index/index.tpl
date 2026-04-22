{if !$request_async}{include file="includes/head.tpl"}{/if}

<h1>Hello Template</h1>

{if $content.error}
    <div class="error">{$content.error}</div>
{/if}
{form.test->display}

{if !$request_async}{include file="includes/footer.tpl"}{/if}