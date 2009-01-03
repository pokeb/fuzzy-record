<html>
<body>
<div style="border: 1px solid #333; background: #FFF; position: absolute; left: 1em; top: 1em; right: 1em; z-index: 1000;">
<div style="position: relative; border: 0; margin: 0;">
<h1 style="font-family: sans-serif; font-weight: bold; font-size: 0.8em; padding: 0.5em; margin: 0; background: #666; color: #FFF">
<?= count(FuzzyTest::$results); ?> tests run, <?= FuzzyTest::$test_failures; ?> failed
</h1>
<table style="margin: 0; padding: 0; border: 0; width: 100%" cellspacing="0">
<tr>
<th style="vertical-align: top; font-family: sans-serif; font-size: 0.8em; text-align: left; padding: 0.5em; background: #CCC;">Class</th>
<th style="vertical-align: top; font-family: sans-serif; font-size: 0.8em; text-align: left; padding: 0.5em; background: #CCC;">Function</th>
<th style="vertical-align: top; font-family: sans-serif; font-size: 0.8em; text-align: left; padding: 0.5em; background: #CCC;">Status</th>
<th style="vertical-align: top; font-family: sans-serif; font-size: 0.8em; text-align: left; padding: 0.5em; background: #CCC;">Line</th>
<th style="vertical-align: top; font-family: sans-serif; font-size: 0.8em; text-align: left; padding: 0.5em; background: #CCC;">Message</th>
</tr>
<?php
$file = "";
foreach (FuzzyTest::$results as $result) {
	if ($result->file != $file) {
		$file = $result->file;
?>
<th colspan="6" style="vertical-align: top; font-family: sans-serif; font-size: 0.8em; text-align: left; padding: 0.5em; background: #CCC;"><?= $file; ?></th>

<?
	}
?>
<tr>
<td style="vertical-align: top; font-family: sans-serif; font-size: 0.8em; border-bottom: 1px solid #CCC; padding: 0.5em;"><?= $result->class; ?></td>
<td style="vertical-align: top; font-family: sans-serif; font-size: 0.8em; border-bottom: 1px solid #CCC; padding: 0.5em;"><?= $result->function; ?></td>
<td style="vertical-align: top; font-family: sans-serif; font-size: 0.8em; border-bottom: 1px solid #CCC; padding: 0.5em;"><?= $result->success ? 'PASS' : 'FAIL'; ?></td>
<td style="vertical-align: top; font-family: sans-serif; font-size: 0.8em; border-bottom: 1px solid #CCC; padding: 0.5em;"><?= $result->success ? '-' : $result->fail_line; ?></td>
<td style="vertical-align: top; font-family: sans-serif; font-size: 0.8em; border-bottom: 1px solid #CCC; padding: 0.5em;"><?= $result->success ? '-' : $result->fail_message; ?></td>
</tr>
<?php
}
?>
</table>

</div>
</div>
</body>
</html>