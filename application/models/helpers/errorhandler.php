<?php
function myErrorHandler($errno, $errstr, $errfile, $errline){
    switch ($errno) {
    case E_USER_ERROR:
	error_log('[RAP] load parser error:'.$errstr.' error no '.$errno.' file '.$errfile.' errline '.$errline);
	echo 'false';	
        exit(0);
        break;
    }
    /* Don't execute PHP internal error handler */
    return true;
}
?>
