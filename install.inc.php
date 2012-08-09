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

	unset($rxa_realcache);
	include('config.inc.php');

   // Gültige REDAXO-Version abfragen
	if ( !in_array($rxa_realcache['rexversion'], array('3.11', '32', '40', '41', '42')) ) {
		echo '<font color="#cc0000"><strong>Fehler! Ung&uuml;ltige REDAXO-Version - '.$rxa_realcache['rexversion'].'</strong></font>';
		$REX['ADDON']['installmsg'][$rxa_realcache['name']] = '<br /><br /><font color="#cc0000"><strong>Fehler! Ung&uuml;ltige REDAXO-Version - '.$rxa_realcache['rexversion'].'</strong></font>';
		$REX['ADDON']['install'][$rxa_realcache['name']] = 0;
		return;
	}
	
	$count = realcache_removedir($rxa_realcache['cachedir']);

	$REX['ADDON']['install'][$rxa_realcache['name']] = 1;
?>