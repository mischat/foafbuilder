<?php

function check_key($type) {
	if ($type == 'get' && isset($_GET['key'])) {
		if (Zend_Session::getId() == $_GET['key']) {
			return true;
		}
	} elseif ($type == 'post' && isset($_POST['key'])) {
		if (Zend_Session::getId() == $_POST['key']) {
			return true;
		}
	} 
	return false;
}
?>
