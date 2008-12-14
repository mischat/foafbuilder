<?php

$info = parse_url(getenv('DB_DSN'));

/* If there is no password then forget about it*/
if (isset($info['pass'])) {
        ($GLOBALS['db_conn'] = mysql_connect($info['host'], $info['user'], $info['pass'])) || die(mysql_error());
        mysql_select_db(basename($info['path']), $GLOBALS['db_conn']) || die(mysql_error());
} else {
        ($GLOBALS['db_conn'] = mysql_connect($info['host'], $info['user'])) || die(mysql_error());
        mysql_select_db(basename($info['path']), $GLOBALS['db_conn']) || die(mysql_error());
}
unset($info);

require_once 'OAuthServer.php';

/*
 * Initialize OAuth store
 */

require_once 'OAuthStore.php';
OAuthStore::instance('MySQL', array('conn' => $GLOBALS['db_conn']));

?>
