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

	// Name des Addons und Pfade
	unset($rxa_realcache);
	$rxa_realcache['name'] = '_realcache_';

	$REX['ADDON']['version'][$rxa_realcache['name']] = '1.2';
	$REX['ADDON']['author'][$rxa_realcache['name']] = 'Andreas Eberhard';
	$REX['ADDON']['supportpage'][$rxa_realcache['name']] = 'forum.redaxo.de';

	$rxa_realcache['path'] = $REX['INCLUDE_PATH'].'/addons/'.$rxa_realcache['name'];
	$rxa_realcache['basedir'] = dirname(__FILE__);
	$rxa_realcache['lang_path'] = $REX['INCLUDE_PATH']. '/addons/'. $rxa_realcache['name'] .'/lang';
	$rxa_realcache['cachedir'] = $REX['INCLUDE_PATH'].'/addons/'.$rxa_realcache['name'] .'/cache';
	$rxa_realcache['meldung'] = '';
	$rxa_realcache['rexversion'] = isset($REX['VERSION']) ? $REX['VERSION'] . $REX['SUBVERSION'] : $REX['version'] . $REX['subversion'];

/**
 * --------------------------------------------------------------------
 * Workaround für file_get_contents und file_put_contents
 * --------------------------------------------------------------------
 */
	if (!function_exists('file_get_contents'))
	{
		function file_get_contents($filename)
		{
			$fp = fopen( $filename, 'r' );
			if (!$fp)
			{
				return false;
			}
			return fread($fp, filesize($filename));
		}
	}

	if (!function_exists('file_put_contents'))
	{
		function file_put_contents($path, $content)
		{
			$fp = @fopen($path, "wb");
			if ($fp)
			{
				fwrite($fp, $content);
				fclose($fp);
			}
		}
	}

/**
 * --------------------------------------------------------------------
 * Bei POST oder GET mit zusätzlichen Werten kein Cache!
 * --------------------------------------------------------------------
 */
	if (!function_exists('realcache_check_cache'))
	{
		function realcache_check_cache()
		{
			if (isset($_POST) and count($_POST) > 0)
			{
				return true;
			}
			if (isset($_GET) and count($_GET) > 1)
			{
				foreach ($_GET as $key => $value)
				{
					if (($key <> 'article_id') and ($key <> 'clang'))
					{
						return true;
					}
				}
			}
		}
	}
	
/**
 * --------------------------------------------------------------------
 * Prüfen ob die aktuelle Kategorie mit der Auswahl übereinstimmt
 * --------------------------------------------------------------------
 */
	if (!function_exists('realcache_check_cat'))
	{
		function realcache_check_cat($acat, $aart, $subcats, $realcache_cats)
		{

			// prüfen ob Kategorien ausgewählt
			if (!is_array($realcache_cats)) return false;

			// aktuelle Kategorie in den ausgewählten dabei?
			if (in_array($acat, $realcache_cats)) return true;

			// Prüfen ob Parent der aktuellen Kategorie ausgewählt wurde
			if ( ($acat > 0) and ($subcats == 1) )
			{
				$cat = OOCategory::getCategoryById($acat);
				while($cat = $cat->getParent())
				{
					if (in_array($cat->_id, $realcache_cats)) return true;
				}
			}

			// evtl. noch Root-Artikel prüfen
			if (strstr(implode('',$realcache_cats), 'r'))
			{
				if (in_array($aart.'r', $realcache_cats)) return true;
			}

			// ansonsten keine Ausgabe!
			return false;
		}
	}

/**
 * --------------------------------------------------------------------
 * Verzeichnis leeren
 * --------------------------------------------------------------------
 */
	if (!function_exists('realcache_removedir'))
	{
		function realcache_removedir($dir)
		{
			$handle = opendir( $dir );
			if (!isset($count)) $count = 0;
			while ( $file = readdir ( $handle ) )
			{
				if ( eregi( "^\.{1,2}$", $file ) ) continue;
				if ( is_dir( $dir."/".$file ) )
				{
					$count .= realcache_removedir($dir."/".$file);
					rmdir ($dir."/".$file);
				}
				else
				{
					unlink ("$dir/$file");
					$count = $count + 1;
				}
			}
			closedir ($handle);
			return $count;
		}
	}
	
/**
 * --------------------------------------------------------------------
 * Outputfilter für das Frontend, Cache schreiben
 * --------------------------------------------------------------------
 */
	if (!function_exists('realcache_opf'))
	{
		function realcache_opf($params)
		{
			global $REX, $REX_ARTICLE;
			global $rxa_realcache, $article_id, $clang;

			if (!isset($clang)) $clang = '0';
			if (!isset($article_id) or ($article_id == '')) $article_id = '0';

			$content = $params['subject'];

			// Einstellungen aus ini-Datei laden
			if (($lines = file($rxa_realcache['path'].'/'.$rxa_realcache['name'].'.ini')) === FALSE) {
				return $content;
			} else {
				$va = explode(',', trim($lines[0]));
				$allcats = trim($va[0]);
				$subcats = trim($va[1]);
				$cachetime = trim($va[2])+0;
				$realcache_cats = array();
				$realcache_cats = unserialize(trim($lines[1]));
			}
			
			// aktuellen Artikel ermitteln
			$artid = isset($_GET['article_id']) ? $_GET['article_id']+0 : 0;
			if ($artid==0) {
				$artid = $REX_ARTICLE->getValue('article_id')+0;
			}
			if ($artid==0) { $artid = $REX['START_ARTICLE_ID']; }

			if (!$artid) { return $content; }

			$article = OOArticle::getArticleById($artid);
			if (!$article) { return $content; }

			// aktuelle Kategorie ermitteln
			if ( in_array($rxa_realcache['rexversion'], array('3.11')) ) {
				$acat = $article->getCategoryId();
			}
			if ( in_array($rxa_realcache['rexversion'], array('32', '40', '41', '42')) ) {
				$cat = $article->getCategory();
				if ($cat) {
					$acat = $cat->getId();
				}
			}
			// Wenn keine Kategorie ermittelt wurde auf -1 setzen für Prüfung in realcache_check_cat, Prüfung auf Artikel im Root
			if (!isset($acat) or !$acat) { $acat = -1; }

			// Array anlegen falls keine Kategorien ausgewählt wurden
			if (!is_array($realcache_cats)){
				$realcache_cats = array();
			}

			// Cache ausgeben
			if ( ($allcats==1) or (realcache_check_cat($acat, $artid, $subcats, $realcache_cats) == true) )
			{
				if (!file_exists($rxa_realcache['cachedir'] . '/' . $article_id . '.' . $clang . ".txt"))
				{
					@file_put_contents($rxa_realcache['cachedir'] . '/' . $article_id . '.' . $clang . ".txt", $content . '<!-- _RealCache_ '.$REX['ADDON']['version'][$rxa_realcache['name']].' - cached version created '.date('d.m.Y H:i:s').' -->');
				}
			}
		}
	}
	
/**
 * --------------------------------------------------------------------
 * Outputfilter für das Frontend, Cache-Vesion ausgeben
 * --------------------------------------------------------------------
 */
	if (!function_exists('realcache_get_cache'))
	{
		function realcache_get_cache($params)
		{
			global $REX, $REX_ARTICLE;
			global $rxa_realcache, $article_id, $clang;
			
			if (!isset($clang)) $clang = '0';
			if (!isset($article_id) or ($article_id == '')) $article_id = '0';

			// Einstellungen aus ini-Datei laden
			if (($lines = file($rxa_realcache['path'].'/'.$rxa_realcache['name'].'.ini')) === FALSE) {
				return;
			} else {
				$va = explode(',', trim($lines[0]));
				$allcats = trim($va[0]);
				$subcats = trim($va[1]);
				$cachetime = trim($va[2])+0;
				$realcache_cats = array();
				$realcache_cats = unserialize(trim($lines[1]));
			}

			// nichts für Caching ausgewählt
			if (($allcats == 0) and ($realcache_cats == ''))
			{
				return;
			}

			// evtl. bestehende Cache-Version löschen
			clearstatcache();
			$timestamp = time();
			$filetimestamp = @filemtime($rxa_realcache['cachedir'] . '/' . $article_id . '.' . $clang . ".txt") + ($cachetime * 60);
			if ($filetimestamp < $timestamp)
			{
				@unlink($rxa_realcache['cachedir'] . '/' . $article_id . '.' . $clang . ".txt");
			}

			// Prüfen ob Cache-Version vorhanden - wenn ja dann ausgeben und Ende
			$nocache = realcache_check_cache();
			$content = @file_get_contents($rxa_realcache['cachedir'] . '/' . $article_id . '.' . $clang . ".txt");
			if (($content) and !$nocache)
			{
				ob_end_clean();
				if (isset($REX['USE_GZIP']) and (($REX['USE_GZIP'] === 'true') or ($REX['USE_GZIP'] === 'frontend')))
				{
					if (function_exists('rex_send_gzip'))
					{
						echo rex_send_gzip($content);
					}
				}
				else
				{
					echo $content;
				}
				exit;
			}

		}
	}

/**
 * --------------------------------------------------------------------
 * Cache im Backend löschen (bei Änderung eines Artikels)
 * --------------------------------------------------------------------
 */
	if (!function_exists('realcache_artgenerated'))
	{
		function realcache_artgenerated($article_id)
		{
			global $REX, $REX_ARTICLE;
			global $rxa_realcache, $article_id, $clang;

			@unlink($rxa_realcache['cachedir'] . '/' . $article_id . '.' . $clang . ".txt");
			@unlink($rxa_realcache['cachedir'] . '/' . '0' . '.' . $clang . ".txt");
		}
	}
	
	if (!function_exists('realcache_opf_rex3'))
	{
		function realcache_opf_rex3($params)
		{
			global $REX, $REX_ARTICLE;
			global $rxa_realcache, $article_id, $clang, $page, $mode, $function;

			if ($rxa_realcache['rexversion'] == '3.11')
			{
				$content = $params['subject'];
			}

			if ((!isset($mode) or (isset($mode) and $mode == '')) and isset($_GET['mode'])) $mode = $_GET['mode'];
			if ((!isset($mode) or (isset($mode) and $mode == '')) and isset($_POST['mode'])) $mode = $_POST['mode'];

			if ((!isset($function) or (isset($function) and $function == '')) and isset($_GET['function'])) $function = $_GET['function'];
			if ((!isset($function) or (isset($function) and $function == '')) and isset($_POST['function'])) $function = $_POST['function'];

			if (($page == 'content') and ($mode == 'edit') and ($function == 'add' or $function == 'edit' or $function == 'delete' or $function == 'moveup' or $function == 'movedown'))
			{
				@unlink($rxa_realcache['cachedir'] . '/' . $article_id . '.' . $clang . ".txt");
				@unlink($rxa_realcache['cachedir'] . '/' . '0' . '.' . $clang . ".txt");
			}

			if ($rxa_realcache['rexversion'] == '3.11')
			{
				return $content;
			}

		}
	}

/**
 * --------------------------------------------------------------------
 * Cache im Backend löschen (bei "System->Cache löschen" bzw. "Specials->Regeneriere Artikel & Cache"
 * --------------------------------------------------------------------
 */
	if (!function_exists('realcache_allgenerated'))
	{
		function realcache_allgenerated()
		{
			global $rxa_realcache;

			realcache_removedir($rxa_realcache['cachedir']);
		}
	}

/**
 * --------------------------------------------------------------------
 * Nur im Backend
 * --------------------------------------------------------------------
 */
	if (!$REX['GG']) {
		// Sprachobjekt anlegen
		$rxa_realcache['i18n'] = new i18n($REX['LANG'],$rxa_realcache['lang_path']);

		// Anlegen eines Navigationspunktes im REDAXO Hauptmenu
		$REX['ADDON']['page'][$rxa_realcache['name']] = $rxa_realcache['name'];
		// Namensgebung für den Navigationspunkt
		$REX['ADDON']['name'][$rxa_realcache['name']] = $rxa_realcache['i18n']->msg('menu_link');

		// Berechtigung für das Addon
		$REX['ADDON']['perm'][$rxa_realcache['name']] = $rxa_realcache['name'].'[]';
		// Berechtigung in die Benutzerverwaltung einfügen
		$REX['PERM'][] = $rxa_realcache['name'].'[]';
	}
	
/**
 * --------------------------------------------------------------------
 * Im Backend Cache löschen
 * --------------------------------------------------------------------
 */
 
	// REDAXO 3.x
	if (!$REX['GG'] and (in_array($rxa_realcache['rexversion'], array('3.11', '3.2'))))
	{
		rex_register_extension('OUTPUT_FILTER', 'realcache_opf_rex3');
		rex_register_extension('ALL_GENERATED', 'realcache_allgenerated');
	}
	
	// REDAXO 4.x
	if (!$REX['GG'] and (in_array($rxa_realcache['rexversion'], array('40', '41', '42'))))
	{
		rex_register_extension('ARTICLE_GENERATED', 'realcache_artgenerated');
		rex_register_extension('ALL_GENERATED', 'realcache_allgenerated');
	}

/**
 * --------------------------------------------------------------------
 * Extension-Point registrieren, Ausgabe des Cache
 * --------------------------------------------------------------------
 */
	if ($REX['GG'] and (in_array($rxa_realcache['rexversion'], array('32', '40', '41', '42'))))
	{
		// Output-Filter, Cache schreiben
		rex_register_extension('OUTPUT_FILTER_CACHE', 'realcache_opf');
		// Addons-Included, Cache lesen und ausgeben
		//rex_register_extension('ADDONS_INCLUDED', 'realcache_get_cache');
		$params = array();
		realcache_get_cache($params);
	}
	
	if ($REX['GG'] and ($rxa_realcache['rexversion'] == '3.11'))
	{
		// Output-Filter, Cache schreiben
		rex_register_extension('OUTPUT_FILTER_CACHE', 'realcache_opf');
		// Cache lesen und ausgeben
		$params = array();
		realcache_get_cache($params);
	}
?>