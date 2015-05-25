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

class Result
{

    function __construct ()
    {
        $this->root_path = ROOT_PATH;
    }

    function execute ()
    {
        ?>
<a href="./start.php">Старт</a>
<br>
<br>
<?php
        
        $ClopeDB = new ClopeDB();
        
        $sources = $ClopeDB->selectFromSources();
        
        session_start();
        $time = $_SESSION['time_end'] - $_SESSION['time_start'];
        printf('Время выполнения %.4F сек.', $time);
        
        echo '<br>';
        echo '<br>';
        
        echo 'Исходные данные:' . '<br>';
        
        echo '<br>';
        
        foreach ($sources as $source) {
            echo $source[1] . "<br>";
        }
        
        echo '<br>';
        
        $clusters = $ClopeDB->getClusters();
        ?>
<table width="200" border="1">
	<tr>
		<td>id</td>
		<td>height</td>
		<td>width</td>
		<td>size</td>
		<td>транзакции</td>
	</tr>
    <?php
        echo 'Кластеры:';
        foreach ($clusters as $cluster) {
            echo '<tr>';
            foreach ($cluster as $elem) {
                echo '<td>' . $elem . '</td>';
            }
            echo '<td>';
            $clusterTransactions = $ClopeDB->getClusterTransactions(
                    $cluster['id']);
            foreach ($clusterTransactions as $clusterTransaction) {
                ?>
        <?php
                // echo $clusterTransaction['transaction_id'];
                $transactionItems = $ClopeDB->getTransactionItems(
                        $clusterTransaction['transaction_id']);
                ?>
        <?php
                echo implode($transactionItems);
                ?>
        <br>
        <?php
            }
            echo '</td>';
            echo '</tr>';
        }
        ?>
</table>
<?php
    }
}

