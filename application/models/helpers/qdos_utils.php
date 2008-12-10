<?php

require_once('settings.php');
require_once('sparql.php');
require_once('sparql.php');
require_once('double_metaphone.php');

function mangle_url($uri, $name) {
	$url = $uri;

	if ($name) {
		$name = str_replace(" ", "-", trim($name));
		$name = str_replace("/", "-", $name);
	}

	if (isset($_SERVER['SERVER_NAME']) && strlen($_SERVER['SERVER_NAME']) > 0) {
		$url = str_replace("qdos.com", $_SERVER['SERVER_NAME'], $url);
		$url = str_replace("&","&amp;",$url);
	}
	if ($name) {
		$encoded = urlencode($name); /* just in case */
		return preg_replace("/(user|celeb)/", "$1/$encoded", $url) . "/html";
	} else {
		return $url . "/html";
	}
}

function reg_box($action = "claim", $name = "") {
  if (!$name) { $name = $_POST['name']; }
  $email = $_POST['email'];
  $username = $_POST['username'];
  $password = $_POST['password'];
  $age = $_POST['age'];
  $country = $_POST['country'];
  $postcode = $_POST['postcode'];
  $public = $_POST['public'];

  $reqd = '<span style="color: #dd0000;"><abbr title="Required field">*</abbr></span>';
?>
<form method="post" action="<?php echo $action ?>">
  <table>
    <tr><td>Username <?php echo $reqd; ?></td><td><input type="text" name="username" value="<?php echo($username) ?>"></td></tr>
    <tr><td>Password <?php echo $reqd; ?></td><td><input type="password" name="password" value="<?php echo($password) ?>"></td></tr>
    <tr><td>Name <?php echo $reqd; ?></td><td><input type="text" name="name" value="<?php echo($name) ?>"></td></tr>
    <tr><td>Email address <?php echo $reqd; ?></td><td><input type="text" name="email" value="<?php echo($email) ?>"></td></tr>
    <tr><td>Country <?php echo $reqd; ?></td>
      <td>
        <select name="country">
          <option value=""></option><?php
  $link = db_connect();
  $result = mysql_query("SELECT iso, printable_name FROM country");
  while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
    $sel = "";
    if ($country == $line['iso']) {
      $sel = " selected";
    }
    print("          <option$sel value=\"$line[iso]\">$line[printable_name]</option>\n");
  }
?>
        </select>
      </td>
    </tr>
    <tr><td>Postcode / Zipcode</td><td><input type="text" name="postcode" value="<?php echo($postcode) ?>"></td></tr>
    <tr>
      <td>Age range</td>
      <td>
<?php
  age_radio("00-12", "12 or under", $age);
  age_radio("13-17", "13&ndash;17", $age);
  age_radio("18-25", "18&ndash;25", $age);
  age_radio("26-35", "26&ndash;35", $age);
  age_radio("36-45", "36&ndash;45", $age);
  age_radio("46-55", "46&ndash;55", $age);
  age_radio("56-65", "56&ndash;65", $age);
  age_radio("66-99", "66 and over", $age);
?>
      </td>
    </tr>
  </table>
  <input type="submit" value="Submit">
</form>
<?php
}

function age_radio($k, $v, $set) {
  if ($set == $k) {
    print("        <input type=\"radio\" name=\"age\" value=\"$k\" checked>$v</input>\n");
  } else {
    print("        <input type=\"radio\" name=\"age\" value=\"$k\">$v</input>\n");
  }
}

function write_foaf($uri, $outfile, $data) {
	$ttl = "";
	$fh = fopen($outfile, "w");
	if (!$fh) {
		error_log("failed to open FOAF file '$outfile'");
	}
	if (!$uri) {
		$uri = "[]";
	} else {
		$uri = "<$uri>";
	}
	$ttl .= "@prefix foaf: <http://xmlns.com/foaf/0.1/> .\n@prefix q: <http://qdos.com/schema#> .\n\n$uri a foaf:Person ;\n";
	if (isset($_SESSION['name'])) {
		$ttl .= " foaf:name \"$_SESSION[name]\" ;\n";
		$ttl .= name_metaphones($_SESSION['name']);
	}
	if (isset($_SESSION['mbox'])) {
		$ttl .= " foaf:mbox_sha1sum \"$_SESSION[mbox]\" ;\n";
	}
	$ttl .= " <http://www.w3.org/2000/01/rdf-schema#seeAlso> <$_SESSION[ext_uri]> ;\n";
	$vf = "";
	foreach (split("\n", $data) as $line) {
		if (substr($line, 0, 1) != "<") continue;
		$ttl .= "  $line ;\n";
		$parts = split(" ", $line);
		if (isset($_SESSION['verified'][$parts[1]]) &&
		    $_SESSION['verified'][$parts[1]]) {
			$vf .= "$parts[1] a q:VerifiedAccount .\n";
		}
	}
	$ttl .= ".\n";
	$ttl .= $vf;
	if (isset($_SESSION['extra_data'])) {
		$ttl .= $_SESSION['extra_data'];
	}
	fwrite($fh, $ttl);
	fclose($fh);
	$ret = simple_post($GLOBALS['ext'].'tmpstore.pl', 'filename='.urlencode($outfile)."&data=".urlencode($ttl));
	error_log("tmpstore: $ret");
}

function write_mini_foaf($ep, $uri) {
	$ttl = "@prefix foaf: <http://xmlns.com/foaf/0.1/> .\n@prefix q: <http://qdos.com/schema#> .\n\n<$uri> a foaf:Person ;\n";
	if (isset($_SESSION['name'])) {
		$ttl .= " foaf:name \"$_SESSION[name]\" ;\n";
		$ttl .= name_metaphones($_SESSION['name']);
	}
	if (isset($_SESSION['mbox'])) {
		$ttl .= " foaf:mbox_sha1sum \"$_SESSION[mbox]\" ;\n";
	}
	$ttl .= ".\n";

	sparql_put_string($ep, $uri, $ttl);
}

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

function sql_query($query) {
	$res = mysql_query($query);
	if (mysql_errno()) {
		die("Query '$query' failed: ".mysql_error());
	}

	return $res;
}

function display_score($score) {
	print("<table>\n");
	foreach (split(" ", $score) as $break) {
		$lr = split("\|", $break);
		$lr[0] = preg_replace("/.*#/", "", $lr[0]);
		print("<tr><td>$lr[0]</td><td>$lr[1]</td></tr>\n");
	}
	print("</table>\n");
}

function simple_get($url) {
	$fh = fopen($url, 'r') or die("cannot open $url");
	$ret = "";
	while (!feof($fh)) {
		$ret .= fread($fh, 8192);
	}
	fclose($fh);

	return $ret;
}

function complex_get($url) {
	$ch = curl_init();

	//curl_setopt($ch, CURLOPT_VERBOSE, 1);
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_GET, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_USERAGENT, "CURL");
	$http_result = curl_exec($ch);
	curl_close($ch);

	return $http_result;
}

function simple_post($url, $data) {
	$ch = curl_init();

	//error_log($url." ".$data);
	//curl_setopt($ch, CURLOPT_VERBOSE, 1);
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$http_result = curl_exec($ch);
	curl_close($ch);

	return $http_result;
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

function user_session($name, $uri, $mbox) {
  global $ep;
  global $ext;

  $_SESSION['signin'] = 1;
  $_SESSION['name'] = $name;
  $_SESSION['mbox'] = $mbox;
  $_SESSION['uri'] = $uri;
  error_log("Signin $uri");
  $ext_uri = preg_replace("/(\.ttl)?$/", "-ext\$1", $uri, 1);
  $_SESSION['ext_uri'] = $ext_uri;
  $data = "";
  if (substr($uri, 0, 5) == "file:") {
    $_SESSION['public'] = 0;
    $foaf_uri = preg_replace("/^file:/", $ext, $uri);
    $q = "SELECT ?p ?o ?type FROM <$foaf_uri> WHERE { <$uri> ?p ?o OPTIONAL { ?o a ?type } }";
    //error_log("PUL SPARQL: ".$q);
    $res = split("\n", `/usr/local/bin/roqet -q -e '$q' $uri`);
    foreach ($res as $row) {
      //print($row."\n");
      if (preg_match("/p=uri(<.*?>), o=uri(<.*?>), type=(.*)\]/", $row, $vars)) {
        if ($vars[1] != '<http://www.w3.org/1999/02/22-rdf-syntax-ns#type>' &&
            $vars[1] != '<http://www.w3.org/2000/01/rdf-schema#seeAlso>' &&
	    $vars[1] != '<http://xmlns.com/foaf/0.1/name>') {
          //print(join("|", $vars)."\n");
	  $data .= $vars[1].' '.$vars[2]."\n";
	  if ($vars[3] == "uri<http://qdos.com/schema#VerifiedAccount>") {
	    $_SESSION['verified'][$vars[2]] = 1;
	  }
        }
      }
    }
  } else {
    $_SESSION['public'] = 1;
    $_SESSION['extra_data'] = "";
    $q = "SELECT ?p ?o ?type WHERE { GRAPH <$uri> { <$uri> ?p ?o OPTIONAL { ?o a ?type } } }";
    //print($q."\n");
    $res = sparql_query($ep, $q);
    foreach ($res as $row) {
      if (substr($row['?o'], 0, 1) == "<" &&
          $row['?p'] != '<http://www.w3.org/1999/02/22-rdf-syntax-ns#type>' &&
          $row['?p'] != '<http://www.w3.org/2000/01/rdf-schema#seeAlso>' &&
	  $row['?p'] != '<http://xmlns.com/foaf/0.1/name>') {
        $data .= $row['?p'].' '.$row['?o']."\n";
        if ($row['?type'] == "<http://qdos.com/schema#VerifiedAccount>") {
          $_SESSION['verified'][$row['?o']] = 1;
        }
        //print $row['?p'].' '.$row['?o'].' '.$row['?type']."\n";
      }
      if ($row['?p'] == '<http://www.w3.org/1999/02/22-rdf-syntax-ns#type>' &&
	  $row['?o'] != '<http://xmlns.com/foaf/0.1/Person') {
        $_SESSION['extra_data'] .= "<$uri> a ".$row['?o']." .\n";
      }
    }
  }
  $_SESSION['data'] = $data;
  $_SESSION['score'] = simple_get($ext."ret-score.pl?user=".urlencode($uri));
}

function db_connect() {
	global $db, $dbserver, $dbuser;
	$link = mysql_pconnect($dbserver, $dbuser, '')
	    or die("Could not connect to $db@$dbserver: " . mysql_error());
	mysql_select_db($db) or error_log('Could not select database');

	return $link;
}

function cache_filename($uri) {
    $hash = md5($uri);
    preg_match('/(..)(..)(.*)/', $hash, $matches);
    return 'cache/'.$matches[1].'/'.$matches[2].'/'.$matches[3];
}

function get_sql_variable($v) {
  db_connect();
  $vi = substr($v, 0, 16);
  $result = sql_query("SELECT value FROM variables WHERE name='$vi'");
  if ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
    return $line['value'];
  }

  die("tried to fetch undefined variable $v");
}

function set_sql_variable($v, $val) {
  db_connect();
  $vi = substr($v, 0, 16);
  sql_query("REPLACE INTO variables VALUES('$vi', '$val')");
}

/* takes a URI to score, returns an array of connectedness, etc. normalised in
 * [0,1] */
function rel_score($uri) {
  $line = false;
  $link = db_connect();
  $max = 1;
  $query = "SELECT connectedness+connectedness_i AS connectedness, impact+impact_i AS impact, activity+activity_i AS activity, clarity+clarity_i AS clarity FROM scores WHERE uri='".mysql_escape_string($uri)."'";
  $result = sql_query($query,$link);
  if ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
    foreach (Array("connectedness", "impact", "activity", "clarity") as $k => $factor) {
      $max = get_sql_variable("max_".$factor);
      $line[$factor] /= $max;
      if ($line[$factor] > 1.0) {
	$line[$factor] = 1.0;
      } else if ($line[$factor] < 0.1) {
        $line[$factor] = 0.1;
      }
    }
  }

  return $line;
}

/**
 * Get current QDOS rank from scores table. 
 */
function qdos_rank($uri) {
	$link = db_connect();
	
	$rank = -1;
	$query = "SELECT current_rank AS rank FROM scores WHERE uri='".mysql_escape_string($uri)."'";
	$result = sql_query($query,$link);
	if ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
		$rank = $line['rank']; 
	}
	
	return $rank;
}

/**
 * Get QDOS score change indicator from scores table (1, 0 or -1).
 */
function qdos_score_change($uri) {
	$link = db_connect();
	
	$change = 0;
	$query = "SELECT current_qdos AS current, previous_qdos AS previous FROM scores WHERE uri='".mysql_escape_string($uri)."'";
	$result = sql_query($query,$link);
	if ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
		$current = $line['current'];
		$previous = $line['previous'];
		if ($current != $previous)
			$change = $current > $previous ? 1 : -1; 
	}
	
	return $change;
}


/* takes a URI, grabs the score from the database and return the score.*/
function qdos_score($uri, $part = 'score', $factor = 0) {
  	$link = db_connect();
	
	$score = 1;

	$query = "SELECT current_qdos AS current FROM scores WHERE uri='".mysql_escape_string($uri)."'";
	$result = sql_query($query,$link);
	if ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
		$score = $line['current'];
	}
	
	return $score;
}

function qdos_factor() {
  $link = db_connect();

  error_log("qdos_factor() is deprecated, dont use");
  $factor = 1.0;
  $result = sql_query("SELECT max(score) AS max from scores");
  if ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
    $factor = $GLOBALS['score_upper'] / sqrt($line['max']);
  }

  return $factor;
}

function geoip_country($ip) { 
	$link = db_connect();
 
        if (preg_match("/^([0-9]+)\.([0-9]+)\.([0-9]+)\.([0-9]+)$/", $ip, $matches)) { 
                $ipnum = $matches[1] * 4194304 + $matches[2] * 16384 + $matches[3] * 64 + $matches[4] / 4; 
                $res = mysql_query("SELECT country FROM geoip WHERE $ipnum * 4 >= min AND $ipnum * 4 <= max"); 
                $ret = "Intl"; 
                if ($row = mysql_fetch_assoc($res)) { 
                        $ret = $row['country']; 
                } 
                mysql_free_result($res); 
 
                return $ret; 
        } else if ($ip == '::1') {
		return "Intl"; /* IPv6 localhost gets international behaviour */
        }
 
        error_log("Bad IP syntax $ip"); 
        return "ERR"; 
} 

/* Returns total number of rows in scores tables */
function get_qdos_count() {
  	$link = db_connect();
	
	$count = 0;

	$query = "SELECT count(*) AS count FROM scores";
	$result = sql_query($query,$link);
	if ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
		$count = $line['count'];
	}
	
	return $count;
}
?>
