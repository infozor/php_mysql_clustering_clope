<?php
namespace _Test;
$cur_path = empty($_SERVER['VHOST_DIR']) ? dirname(__FILE__) . '/' : $_SERVER['VHOST_DIR'] .
         '/';
$pos = strpos($cur_path, '_Test');
$place_path = substr($cur_path, 0, $pos);
$place_path = str_replace("\\", "/", $place_path);

define('ROOT_PATH', $place_path);

require_once $place_path . '_DB/DB.php';
require_once $place_path . '_Clope/ClopeDB.php';

use Exception;
use ErrorException;
use _Clope\DB;
use _Clope\ClopeDB;

class Mashroom
{

    function __construct ()
    {
        $this->root_path = ROOT_PATH;
        set_error_handler(
                function  ($errno, $errstr, $errfile, $errline, 
                        array $errcontext)
                {
                    if (0 === error_reporting()) {
                        return false;
                    }
                    throw new ErrorException($errstr, 0, $errno, $errfile, 
                            $errline);
                });
    }

    function execute ()
    {
        // читаем данные из файла agaricus-lepiota.data в массив $sources_array
        // файл взят из источника:
        // https://archive.ics.uci.edu/ml/machine-learning-databases/mushroom/agaricus-lepiota.data
        try {
            $file = $this->root_path . 'buf/' . 'agaricus-lepiota.data';
            $source = file_get_contents($file);
            $source = str_replace(",", "", $source);
            
            $file = $this->root_path . 'buf/' . 'agaricus-lepiota.txt';
            file_put_contents($file, $source);
        } catch (Exception $e) {
            die($e);
        }
        echo ('готово');
    }
}
