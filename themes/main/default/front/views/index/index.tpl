{if !$request_async}{include file="includes/head.tpl"}{/if}

<h1>Hello Template</h1>

{form.test->display}

{if !$request_async}{include file="includes/footer.tpl"}{/if}