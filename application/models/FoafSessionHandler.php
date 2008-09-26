<?php

class FoafSessionHandler implements Zend_Session_SaveHandler_Interface {
	
    function close() {
        return true;
    }
    function open($save_path,$name) {
        return true;
    }

    //TODO This needs to do some error catching, no dir, no write permission etc
    function write($id,$data)   {
        $dir_path = "/tmp/foafeditor_sessions";
        if( !is_dir($dir_path)){
            mkdir( $dirPath );
        }
        $session_file = $dir_path."/session_".$id;
        $handle = @fopen($session_file, "w");
        if ($handle) {
            $return = fwrite($handle, $data);
            fclose($handle);
            return $return;
        } else {
            return 0;
        }			
    }

    function read($id) {
        $session_file = "/tmp/foafeditor_sessions/session_$id";
        return (string)@file_get_contents($session_file);	
    }

    function destroy($id) {
        $session_file = "/tmp/foafeditor_sessions/session_$id";
        return (unlink($session_file));
    }

    //TODO rename function
    function gc($maxlifetime) {
        //a test run
        $dir_path = "/tmp/foafeditor_sessions";
        if( !is_dir($dir_path)){
            mkdir( $dirPath );
        }

        $session_file = $dir_path."/gc_test_".$id;
        $handle = @fopen($session_file, "w");
        if ($handle) {
            $return = fwrite($handle, $data);
            fclose($handle);
            return $return;
        } else {
            return false;
        }
    }

}
/* vi:set expandtab sts=4 sw=4: */
