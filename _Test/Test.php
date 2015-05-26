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
require_once $place_path . '_Clope/Clope.php';

use _Clope\DB;
use _Clope\ClopeDB;
use _Clope\Clope;

class Test
{

    function __construct ()
    {
        $this->root_path = ROOT_PATH;
    }

    function execute ()
    {
        
        // обнуляем таблицу sources
        $ClopeDB = new ClopeDB();

        
      //  $ClopeDB->query("SET NAMES cp1251");
        
        // удаляем все записи в БД
        $ClopeDB->allEmpty();
        
        // читаем данные из файла sources.txt в массив $sources_array
        $file_sources = $this->root_path . 'buf/' . 'sources.txt';
        $handle = fopen($file_sources, "r");
        $sources = fread($handle, filesize($file_sources));
        $sources = str_replace("\r", "", $sources);
        $read_array = explode("\n", $sources);
     
        
        $sources_array = array();
        
        foreach ($read_array as $elem) {
            if (strlen($elem) > 0) {
                $sources_array[] = $elem;
            }
        }
        
        // заполняем таблицу sources данными из массива $sources_array
        for ($i = 0; $i < count($sources_array); $i ++) {
            $ClopeDB->sourceInsert(
                    array(
                            $sources_array[$i]
                    ));
        }
        
        // заполняем таблицы transactions и transaction_items данными транзакций
        $ClopeDB->transactionsInsert();
        
        session_start();
        $_SESSION['time_start'] = microtime(true);
        
        // запускаем кластеризацию (алгоритм CLOPE)
        $Clope = new Clope();
        $Clope->clustering();
        
        $_SESSION['time_end'] = microtime(true);
        
        header("Location: ./result.php");
        exit();
    }
}
