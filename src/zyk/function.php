<?php

/**
 * 循环载入所有文件
 * @author wxw 2020/6/28
 *
 * @param $dir
 */
function zyk_require_dir($dir){
    $handle = opendir($dir);
    while(false !== ($file = readdir($handle))){
        if($file != '.' && $file != '..'){
            $filepath = $dir.'/'.$file;
            if(filetype($filepath) == 'dir'){
                zyk_require_dir($filepath);
            }else{
                // 只载入php文件
                if (pathinfo(strtolower($filepath), PATHINFO_EXTENSION) == 'php') {
                    require_once $filepath;
                }
            }
        }
    }
}

// 载入所有当前文件夹下的所有类库方法
zyk_require_dir(__DIR__.'/func/');
