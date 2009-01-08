<?php

function sparql_query_xml($ep, $query,$headers=Array())
{

	$req = "$ep?query=".urlencode($query);
        //echo("req ".$req."<br>\n");
        //$fh = fopen($req, "r");
        $ch = curl_init();

        //curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_URL, $req);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge(Array("Accept: text/tab-separated-values"), $headers));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, "CURL");
        $curl_res = curl_exec($ch);
        curl_close($ch);

	return $curl_res;
}

function sparql_query($ep, $query, $headers=Array())
{
	$curl_res = sparql_query_xml($ep,$query,$headers);
	$http_result = split("\n", $curl_res);
	
	$header = split("\t", rtrim(array_shift($http_result)));
	$ret = array();
	while ($data = rtrim(array_shift($http_result))) {
		$row = array();
		$col = 0;
		foreach (split("\t", $data) as $val) {
			if ($val == "\n") continue;
			$row[$header[$col++]] = $val;
		}
		array_push($ret, $row);
	}

	return $ret;
}

function sparql_print($res)
{
  echo("<table>");
  foreach ($res as $row) {
    echo("<tr>");
    foreach ($row as $k => $v) {
      echo("<td>$k/".htmlspecialchars($v)."</td>");
    }
    echo("</tr>");
  }
  echo("</table>");
}

function sparql_strip($str)
{
  if (substr($str, 0, 1) == '"' && substr($str, -1) == '"') {
    return substr($str, 1, strlen($str) - 2);
  } else if (substr($str, 0, 1) == '<' && substr($str, -1) == '>') {
    return substr($str, 1, strlen($str) - 2);
  } else if (substr($str, 0, 1) == '"') {
    return substr($str, 1, strlen($str) - 1 - strlen(strrchr($str, '"')));
  }

  return $str;
}

?>
