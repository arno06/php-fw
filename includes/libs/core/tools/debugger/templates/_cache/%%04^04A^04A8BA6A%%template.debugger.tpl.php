<?php /* Smarty version 2.6.18, created on 2014-12-02 14:57:12
         compiled from template.debugger.tpl */ ?>
<?php if ($this->_tpl_vars['is_error']): ?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <base href="<?php echo $this->_tpl_vars['server_url']; ?>
"/>
		<title>Une erreur est apparue !</title>
		<script type="text/javascript" src="<?php echo $this->_tpl_vars['dir_to_components']; ?>
/debugger/Debugger.js"></script>
        <script type="text/javascript">Debugger.error = true;</script>
	</head>
	<body>
<?php endif; ?>
		<style type="text/css"><!--@import URL("<?php echo $this->_tpl_vars['dir_to_components']; ?>
/debugger/Debugger.css");--></style>
		<div id="debug"<?php if ($this->_tpl_vars['open'] || $this->_tpl_vars['is_error']): ?> class="<?php if ($this->_tpl_vars['is_error']): ?>fullscreen<?php else: ?>maximized<?php endif; ?>"<?php endif; ?>>
            <div class="debug_bar">
                <div class="debug_global">
                    <span class="debug_time"><?php echo $this->_tpl_vars['timeToGenerate']; ?>
</span>
                    <span class="debug_memory"><?php echo $this->_tpl_vars['memUsage']; ?>
</span>
                </div>
                <div class="debug_control">
                    <a class="debug_fullscreen"></a><a class="debug_toggle"></a><a class="debug_close"></a>
                </div>
            </div>
			<div class="debug_buttons">
				<div rel="trace">
					<span>&nbsp;</span>Traces&nbsp; <span class="count">(<?php echo $this->_tpl_vars['count']['trace']; ?>
)</span>
				</div>
                <div rel="notice">
					<span>&nbsp;</span>Notices <span class="count">(<?php echo $this->_tpl_vars['count']['notice']; ?>
)</span>
				</div>
				<div rel="warning">
					<span>&nbsp;</span>Warnings <span class="count">(<?php echo $this->_tpl_vars['count']['warning']; ?>
)</span>
				</div>
				<div rel="error">
					<span>&nbsp;</span>Erreurs & Exceptions <span class="count">(<?php echo $this->_tpl_vars['count']['error']; ?>
)</span>
				</div>
				<div rel="query">
					<span>&nbsp;</span>Requ&ecirc;tes SQL <span class="count">(<?php echo $this->_tpl_vars['count']['query']; ?>
)</span>
				</div>
				<div rel="cookie" class="vars disabled">
					cookie <span class="count">(<?php echo $this->_tpl_vars['count']['cookie']; ?>
)</span>
				</div>
				<div rel="session" class="vars disabled">
					session <span class="count">(<?php echo $this->_tpl_vars['count']['session']; ?>
)</span>
				</div>
				<div rel="post" class="vars disabled">
					post <span class="count">(<?php echo $this->_tpl_vars['count']['post']; ?>
)</span>
				</div>
				<div rel="get" class="vars">
					get <span class="count">(<?php echo $this->_tpl_vars['count']['get']; ?>
)</span>
				</div>
			</div>
			<div class="debug_content">
				<div class="debug_console">
					<table class="console" cellpadding="0" cellspacing="0">
						<?php echo $this->_tpl_vars['console']; ?>

					</table>
				</div>
				<div class="debug_vars">
					<pre rel="get"><?php echo $this->_tpl_vars['vars']['get']; ?>
</pre>
					<pre rel="post" style="display:none;"><?php echo $this->_tpl_vars['vars']['post']; ?>
</pre>
					<pre rel="session" style="display:none;"><?php echo $this->_tpl_vars['vars']['session']; ?>
</pre>
					<pre rel="cookie" style="display:none;"><?php echo $this->_tpl_vars['vars']['cookie']; ?>
</pre>
				</div>
			</div>
		</div>
<?php if ($this->_tpl_vars['is_error']): ?>
	</body>
</html>
<?php endif; ?>