<?php

    //Create the filename used for the hashing of rdf
    function cache_filename($uri) {
        $hash = md5($uri);
        preg_match('/(..)(..)(.*)/', $hash, $matches);
        return '/'.$matches[1].'/'.$matches[2].'/'.$matches[3];
    } //end cache filename
    
    //Create the cache file directory structure needed
    function create_cache($filename,$datadir) {
            if (preg_match('/\/(..)\/(..)\/(.*)/',$filename,$matches)) {
                    if (!(file_exists("$datadir/$matches[1]"))) {
                            mkdir("$datadir/$matches[1]");
                    }
                    if (!(file_exists("$datadir/$matches[1]/$matches[2]"))) {
                            mkdir("$datadir/$matches[1]/$matches[2]");
                    }
                    return true;
            } else {
                    //Incorrect cache filestructure passed
                    return false;
            }
    }

    function makeOpenIDUrl ($url) { 
	$url = preg_replace('/^https{0,1}:\/\//','',$url); 
        $url = preg_replace('/\/$/','',$url);
	$url = urlencode ($url); 
        $url = preg_replace('/%2F/',"/",$url);

	return $url;  
    }
?>
