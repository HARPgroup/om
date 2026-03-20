<?php


function gzip($src, $level = 5, $dst = false){
    if($dst == false){
        $dst = $src.".gz";
    }
    if(file_exists($src)){
        $filesize = filesize($src);
        $src_handle = fopen($src, "r");
        if(!file_exists($dst)){
            $dst_handle = gzopen($dst, "w$level");
            while(!feof($src_handle)){
                $chunk = fread($src_handle, 2048);
                gzwrite($dst_handle, $chunk);
            }
            fclose($src_handle);
            gzclose($dst_handle);
            return true;
        } else {
            error_log("$dst already exists");
        }
    } else {
        error_log("$src doesn't exist");
    }
    return false;
}

function gunzip($dst, $src){
    if($dst == false){
        $dst = $src.".gz";
    }
    if(file_exists($src)){
        $filesize = filesize($src);
        $src_handle = gzopen($src, "r");
        if(!file_exists($dst)){
            $dst_handle = fopen($dst, "w");
            while(!feof($src_handle)){
                $chunk = gzread($src_handle, 2048);
                fwrite($dst_handle, $chunk);
            }
            gzclose($src_handle);
            fclose($dst_handle);
            return true;
        } else {
            error_log("$dst already exists");
        }
    } else {
        error_log("$src doesn't exist");
    }
    return false;
}

?>