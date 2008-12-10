<?php
require_once('sparql.php');
require_once('double_metaphone.php');
//TODO metaphone
function name_metaphones($name) {
	$ttl = "";
	foreach (split("[ -']", $name) as $k => $cmp) {
		$phones = double_metaphone($cmp);
		if ($phones) {
			foreach($phones as $k2 => $phone) {
				if ($phone) {
					$ttl .= " q:metaphone \"$phone\" ;\n";
				}
			}
		} else {
			$ttl .= " q:metaphone \"$cmp\" ;\n";
		}
	}

	return $ttl;
}

function sparql_put($ep, $uri, $localfile) {
	$fp = @fopen($localfile, "r");
        if (!$fp) return 404;

	$ch = curl_init();
	//error_log("PUT $localfile -> $uri");
	//curl_setopt($ch, CURLOPT_VERBOSE, 1);
	curl_setopt($ch, CURLOPT_URL, $ep.urlencode($uri));
	curl_setopt($ch, CURLOPT_PUT, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_INFILE, $fp);
	curl_setopt($ch, CURLOPT_INFILESIZE, filesize($localfile));
	curl_setopt($ch, CURLOPT_HTTPHEADER, Array("Content-Type: application/x-turtle", "Expect:"));

	$http_result = curl_exec($ch);
	$error = curl_error($ch);
	$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

	curl_close($ch);
	fclose($fp);

	if ($http_code != "201" && $error) {
	   error_log("Error PUTing to '$ep.".urlencode($uri)."' $error");
	}

	return $http_code;
}

function sparql_put_string($ep, $uri, $str) {
	$ch = curl_init();
        $temp = tmpfile();
        fwrite($temp, $str);
        fseek($temp, 0);
	error_log("PUT $str $temp -> $uri");
	//curl_setopt($ch, CURLOPT_VERBOSE, 1);
	curl_setopt($ch, CURLOPT_URL, $ep.urlencode($uri));
	curl_setopt($ch, CURLOPT_PUT, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_INFILE, $temp);
	curl_setopt($ch, CURLOPT_INFILESIZE, strlen($str));
	curl_setopt($ch, CURLOPT_HTTPHEADER, Array("Content-Type: application/x-turtle", "Expect:"));

	$http_result = curl_exec($ch);
	$error = curl_error($ch);
	$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

	curl_close($ch);
	fclose($temp);

	if ($http_code != "201" && $error) {
	   error_log("Error PUTing to '$ep.".urlencode($uri)."' $error");
	}

	return $http_code;
}

function sparql_delete($ep, $uri) {
	error_log("DELETE $uri");
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_VERBOSE, 1);
	curl_setopt($ch, CURLOPT_URL, $ep.urlencode($uri));
	$header = "DELETE ".$uri." HTTP/1.0\r\n";
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $header);

	$http_result = curl_exec($ch);
	$error = curl_error($ch);
	$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);

	if ($http_code != "201" && $error) {
	   error_log("Error DELETEing on '$ep.".urlencode($uri)."' $error");
	}

	return $http_code;
}


function db_connect() {
	global $db, $dbserver, $dbuser;
	$link = mysql_pconnect($dbserver, $dbuser, '')
	    or die("Could not connect to $db@$dbserver: " . mysql_error());
	mysql_select_db($db) or error_log('Could not select database');

	return $link;
}

?>
