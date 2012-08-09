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
?>

<table border="0" width="770" class="rex-table">
  <tr>
    <td class="grey" style="padding:10px;">
<?php 
	if (strstr($REX['LANG'],'utf8'))
	{
		echo utf8_encode(nl2br(htmlspecialchars(file_get_contents($rxa_realcache['path'].'/changelog.txt'))));
	}
	else
	{
		echo nl2br(htmlspecialchars(file_get_contents($rxa_realcache['path'].'/changelog.txt')));
	}
?>
    </td>
  </tr>
</table>