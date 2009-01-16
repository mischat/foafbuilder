<?php

define('FOAF_ROOT', 'http://foaf.qdos.com/');
define('FOAF_EP', 'http://luma:8083/sparql/');
define('FOAF_ALT_EP', 'http://luma:8082/sparql/');
define('QDOS_EP', 'http://luma:8080/sparql/');
define('QDOS_EXT', 'http://extractor.qdos.com:8081/');
define('FOAF_PATH', '/usr/local/src/qdos-dev/foaf');
define('QDOS_ROOT', 'http://qdos.com/');
define('EXAMPLE_OPENID', 'example.org/myopenid');
define('SUPPORT_EMAIL', 'mailto:support@qdos.com');

/*The IFP_BLACKLISt*/

$ifpblacklist = array("<mailto:>",'"da39a3ee5e6b4b0d3255bfef95601890afd80709"','"08445a31a78661b5c746feff39a9db6e4e2cc5cf"','""','"20cb76cb42b39df43cb616fffdda22dbb5ebba32"','<http://www.google.com/>','<http://www.google.com>','<http://www.bbc.co.uk/>','<http://bbc.co.uk>','"02085a0d20a5f574c1ce6cfe42bba6e85cfe07cf"');
define ('IFP_BLACKLIST',serialize($ifpblacklist));

define ('FRIENDS_THRESHOLD', 100);
/*Live Settings*/
define('BUILDER_URL','http://foafbuilder.qdos.com/');
define('IMAGE_URL',BUILDER_URL.'images/');
define('PUBLIC_URL',BUILDER_URL.'people/');
define('PRIVATE_URL','http://private.qdos.com/oauth/');
define('PRIVATE_DATA_DIR', '/usr/local/data/foaf-live/private/oauth');
define('PUBLIC_DATA_DIR','/usr/local/data/foaf-live/public');
define('IMAGE_DATA_DIR','/usr/local/data/foaf-live/images');
define('BUILDER_TEMP_DIR','/tmp/foafbuilder_temporary_file');

define('PRIVATE_EP','http://luma:8090/sparql/');
define('PUBLIC_EP', 'http://luma:8083/sparql/');

/* DEV Settings
define('BUILDER_URL','http://mischa-foafeditor.qdos.com/');
define('IMAGE_URL',BUILDER_URL.'images/');
define('PUBLIC_URL',BUILDER_URL.'people/');
define('PRIVATE_URL','http://private-dev.qdos.com/oauth/');
define('PRIVATE_DATA_DIR', '/usr/local/data/foaf-dev-mischa/private/oauth');
define('PUBLIC_DATA_DIR','/usr/local/data/foaf-dev-mischa/public');
define('IMAGE_DATA_DIR','/usr/local/data/foaf-dev-mischa/images');
define('BUILDER_TEMP_DIR','/tmp/mischafoafeditor_temporary_file');

define('PRIVATE_EP','http://luma:9000/sparql/');
define('PUBLIC_EP', 'http://luma:9001/sparql/');
*/

?>
