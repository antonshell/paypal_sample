<?php
/**
 * Created by PhpStorm.
 * User: Antonshell
 * Date: 10.08.2015
 * Time: 21:44
 */

function default_autoload($className) {
    $system_root = __DIR__ . '/';
    $autoload = ['classes'];

    foreach($autoload as $path) {
        $di = new RecursiveDirectoryIterator($system_root.$path);
        $files = new RecursiveIteratorIterator($di);
        $files->setMaxDepth(2);
        foreach ($files as $filename => $file) {
            if(basename($filename) == $className.'.php')
            {
                include $filename;
                return;
            }
        }
    }
}

spl_autoload_register('default_autoload');