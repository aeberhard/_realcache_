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

	// Include config
	include dirname(__FILE__).'/config.inc.php';

	// Include Header and Navigation
	include $REX['INCLUDE_PATH'].'/layout/top.php';

	// Addon-Subnavigation
	$subpages = array(
		array('',$rxa_realcache['i18n']->msg('menu_settings')),
		array('clear',$rxa_realcache['i18n']->msg('menu_clearcache')),
		array('info',$rxa_realcache['i18n']->msg('menu_information')),
		array('log',$rxa_realcache['i18n']->msg('menu_changelog')),
	);

	// Titel
	if ( in_array($rxa_realcache['rexversion'], array('3.11')) ) {
		title($rxa_realcache['i18n']->msg('title'), $subpages);
	} else {
		rex_title($rxa_realcache['i18n']->msg('title'), $subpages);
	}

	// Include der angeforderten Seite
	if (isset($_GET['subpage'])) {
		$subpage = $_GET['subpage'];
	} else {
		$subpage = '';
	}
	switch($subpage) {
		case 'clear':
			include ($rxa_realcache['path'] .'/pages/clearcache.inc.php');
			include ($rxa_realcache['path'] .'/pages/default_page.inc.php');
		break;
		case 'info':
			include ($rxa_realcache['path'] .'/pages/help.inc.php');
		break;
		case 'log':
			include ($rxa_realcache['path'] .'/pages/changelog.inc.php');
		break;
		default:
			include ($rxa_realcache['path'] .'/pages/default_page.inc.php');
		break;		
	}

	// Include Footer
	include $REX['INCLUDE_PATH'].'/layout/bottom.php';
?>