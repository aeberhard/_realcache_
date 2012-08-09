<?php
/**
 * --------------------------------------------------------------------
 *
 * Redaxo Addon: _realcache_
 * Version: 1.2, 01.09.2008
 *
 * Autor: Andreas Eberhard, andreas.eberhard@gmail.com
 *        http://rex.andreaseberhard.de
 *
 * --------------------------------------------------------------------
 */

	include('config.inc.php');
	if (!isset($rxa_realcache['name'])) {
		echo '<font color="#cc0000"><strong>Fehler! Eventuell wurde die Datei config.inc.php nicht gefunden!</strong></font>';
		return;
	}
		
	echo $rxa_realcache['i18n']->msg('text_help_title');
	$i=1;
	while ($rxa_realcache['i18n']->msg('text_help_'.$i)<>'[translate:text_help_'.$i.']') {
		echo $rxa_realcache['i18n']->msg('text_help_'.$i);
		$i++;
		if ($i>10) { break; }
	}
?>
