<?php

require_once('settings.php');

function mk_cache_path($id) {
	$dir = FOAF_PATH.'/cache/'.preg_replace('/(..)(..)(.*)/', '$1/$2', md5($id));
	if (!file_exists($dir)) {
		mkdir($dir, 0755, TRUE);
	}
}

function image_cache_filename($id) {
	return FOAF_PATH.'/cache/'.preg_replace('/(..)(..)(.*)/', '$1/$2/$3', md5($id));
}

function cache_uri($id) {
	return FOAF_ROOT.'cache/'.preg_replace('/(..)(..)(.*)/', '$1/$2/$3', md5($id));
}

/* arguments:

   ifps - array if ifp values
   inimages - array of image URLs
   size - one of 'sml', 'med' or 'lrg'
*/

function cache_get($ifps, $inimages = array(), $size = 'sml') {
	$ifps = array_unique($ifps);
	$somemissing = 0;
	$retval = array();
	foreach ($ifps as $ifp) {
		$cache_ptr = image_cache_filename($ifp).".ifp";
		if (file_exists($cache_ptr)) {
			if (!$retval) {
				$uris = file($cache_ptr, FILE_IGNORE_NEW_LINES);
				foreach($uris as $uri) {
					$uri = preg_replace('/(.*?)(\.[a-zA-Z0-9]+)$/', '$1-'.$size.'$2', $uri);
					array_push($retval, $uri);
				}
			}
		} else {
			$somemissing = 1;
		}
	}

	if (!$somemissing) {
		if (!$retval) {
			$retval = blank_image($size);
		}

		return $retval;
	}

	// weed out bad images
	$images = array();
	foreach($inimages as $image) {
		if (substr($image, 0, 7) == 'http://') {
			array_push($images, $image);
		}
	}

	$cacheuris = array();
	foreach($images as $key => $image) {
		$imgfh = @fopen($image, 'r');
		if ($imgfh) {
			$ext = preg_replace('/.*?(\.[a-zA-Z0-9]+)?$/', '$1', $image);
			// if we dont get an extension, just guess it's a JPEG
			if (!$ext) {
				$ext = '.jpg';
			}
			$cacheuris[$key] = cache_uri($image).$ext;
			mk_cache_path($image);
			$cachebase = image_cache_filename($image);
			$cachefn = $cachebase.$ext;
			$cachefh = fopen($cachefn, 'w');
			if ($cachefh) {
				while ($data = fread($imgfh, 4096)) {
					fwrite($cachefh, $data);
				}
			}
			fclose($imgfh);
			fclose($cachefh);
			foreach (array('sml' => '60x60', 'med' => '75x75', 'lrg' => '150x150') as $name => $dim) {
				$tmp = rand();
				system("convert $cachefn -resize $dim $cachebase-$name$ext$tmp");
				rename("$cachebase-$name$ext$tmp", "$cachebase-$name$ext");
			}
		}
	}

/*
	if (!$cacheuris) {
		return blank_image($size);
	}
*/

	$created = "";
	foreach ($ifps as $ifp) {
		mk_cache_path($ifp);
		$cache_ptr = image_cache_filename($ifp).'.ifp';
		if (!$created) {
			$fh = fopen($cache_ptr, 'w');
			if ($fh) {
				$created = $cache_ptr;
				if ($cacheuris) {
					fwrite($fh, join($cacheuris, "\n")."\n");
				}
				fclose($fh);
			} else {
				error_log("failed to create $cache_ptr");
			}
		} else {
			if ($created != $cache_ptr) {
				if (file_exists($cache_ptr)) {
					unlink($cache_ptr);
				}
				if (!link($created, $cache_ptr)) {
					error_log("failed to link $cache_ptr to $created");
				}
			}
		}
	}

	$retval = array();

	foreach($cacheuris as $uri) {
		$uri = preg_replace('/(.*?)(\.[a-zA-Z0-9]+)$/', '$1-'.$size.'$2', $uri);
		array_push($retval, $uri);
	}

	if (!$retval) {
		return blank_image($size);
	}

	return $retval;
}

function blank_image($size) {
	return array(FOAF_ROOT.'images/icn-no-photo-'.$size.'.gif');
}

?>
