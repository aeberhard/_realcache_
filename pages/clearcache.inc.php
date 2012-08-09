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

	if (!isset($rxa_realcache['name'])) {
		echo '<font color="#cc0000"><strong>Fehler! Eventuell wurde die Datei config.inc.php nicht gefunden!</strong></font>';
		return;
	}
	
	// Dateien aus dem Ordner files/shadowbox löschen
	clearstatcache();
	$count = realcache_removedir($rxa_realcache['cachedir']);
	
	$rxa_realcache['meldung'] = $rxa_realcache['i18n']->msg('msg_cleared',$count);
	
?>