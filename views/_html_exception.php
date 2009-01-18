<div style="border: 1px solid #333; background: #FFF; position: absolute; left: 1em; top: 1em; bottom: 1em; right: 1em; z-index: 1000;">
<div style="position: relative; border: 0; margin: 0;">
<h1 style="font-family: sans-serif; font-weight: bold; font-size: 0.8em; padding: 0.5em; margin: 0; background: #666; color: #FFF">
<?= "Exception in line ".$exception->getLine()." of ".$exception->getFile().": ".str_replace("\r\n","<br />",$exception->getMessage()); ?>
</h1>
<table style="margin: 0; padding: 0; border: 0; width: 100%" cellspacing="0">
<tr>
<th style="vertical-align: top; font-family: sans-serif; font-size: 0.8em; text-align: left; padding: 0.5em; background: #CCC;">File</th>
<th style="vertical-align: top; font-family: sans-serif; font-size: 0.8em; text-align: left; padding: 0.5em; background: #CCC;">Line</th>
<th style="vertical-align: top; font-family: sans-serif; font-size: 0.8em; text-align: left; padding: 0.5em; background: #CCC;">Function</th>
<th style="vertical-align: top; font-family: sans-serif; font-size: 0.8em; text-align: left; padding: 0.5em; background: #CCC;">Args</th>
</tr>
<?php
foreach ($exception->getTrace() as $trace_line) {
?>
<tr>
<td style="vertical-align: top; font-family: sans-serif; font-size: 0.8em; border-bottom: 1px solid #CCC; padding: 0.5em;"><?= isset($trace_line['file']) ? $trace_line['file'] : '-'; ?></td>
<td style="vertical-align: top; font-family: sans-serif; font-size: 0.8em; border-bottom: 1px solid #CCC; padding: 0.5em;"><?= isset($trace_line['line']) ? $trace_line['line'] : '-'; ?></td>
<td style="vertical-align: top; font-family: sans-serif; font-size: 0.8em; border-bottom: 1px solid #CCC; padding: 0.5em;"><?= $trace_line['function']; ?></td>
<td style="vertical-align: top; font-family: sans-serif; font-size: 0.8em; border-bottom: 1px solid #CCC; padding: 0.5em;">
<ol style="margin: 0; padding: 0 1.0em 0 0; border: 0; background: tansparent;">
<?php
	foreach ($trace_line['args'] as $argument) {
?>
<li style="font-family: sans-serif; font-size: 1em; margin: 0 0 0 0; padding: 0; background: transparent; border: 0; list-style: inside decimal;">
<?= is_object($argument) || is_array($argument) ? print_r($argument) : $argument; ?>
</li>
<?php
	}
?>
</ol>
</td>
</tr>
<?php
}
?>
</table>
</div>
</div>