<?php
namespace _Clope;
require_once '_DB/DB.php';
use _DB\DB;

class ClopeDB extends DB
{

    function __construct ()
    {
        parent::__construct();
    }
    
    // удаление всех записей в БД
    public function allEmpty ()
    {
        // удаление записей из таблицы sources
        $this->sourceEmpty();
        
        // удаление записей из таблиц transactions и transaction_items
        $this->transactionsEmpty();
        
        // очистка области кластеризации - удаление записей из таблиц
        // cluster_transactions, clusters, transactions
        $this->clusteringsEmpty();
    }

    private function clustersEmpty ()
    {
        $sqlstr = sprintf("
      TRUNCATE TABLE
        clusters
      ");
        $this->query($sqlstr);
    }

    private function clusterTransactionRelationsEmpty ()
    {
        $sqlstr = sprintf(
                "
        TRUNCATE TABLE
          cluster_transactions
        ");
        $this->query($sqlstr);
    }

    private function transactionsItemsEmpty ()
    {
        $sqlstr = sprintf(
                "
      TRUNCATE TABLE
        transaction_items
      ");
        $this->query($sqlstr);
    }

    public function sourceEmpty ()
    {
        $sqlstr = sprintf(
                "
      TRUNCATE TABLE 
        sources      
      ");
        $this->query($sqlstr);
    }

    public function clusteringsEmpty ()
    {
        $this->clusterTransactionRelationsEmpty();
        $this->clustersEmpty();
    }

    public function sourceInsert ($record)
    {
        $sqlstr = sprintf(
                "
      INSERT INTO
        sources(
        id,
        content)
      VALUES(
        null,
        '%s'
      )        
      ", $record[0]);
        $this->query($sqlstr);
    }

    public function transactionsEmpty ()
    {
        $this->transactionsItemsEmpty();
        
        $sqlstr = sprintf(
                "
      TRUNCATE TABLE
        transactions
      ");
        $this->query($sqlstr);
    }

    public function transactionsInsert ()
    {
        $sqlstr = sprintf(
                "
      SELECT 
        sources.id,
        sources.content
      FROM
        sources
      ");
        $query = $this->query($sqlstr);
        
        $rows = null;
        while ($array = mysql_fetch_row($query)) {
            $data[] = $array;
        }
        
        for ($i = 0; $i < count($data); $i ++) {
            $sqlstr = sprintf(
                    "
        INSERT INTO
          transactions(
          id,  
          source_id)
        VALUES(
          null,
          '%s'
        )
      ", $data[$i][0]);
            
            $this->query($sqlstr);
            $last_transaction_id = $this->last_insert_id();
            
            $transaction_items = str_split($data[$i][1]);
            
            for ($j = 0; $j < count($transaction_items); $j ++) {
                $sqlstr = sprintf(
                        "
          INSERT INTO
            transaction_items(
            id,
            transaction_id,
            item)
          VALUES(
            null,
            '%s',
            '%s'  
          )
        ", 
                        $last_transaction_id, $transaction_items[$j]);
                $this->query($sqlstr);
            }
        }
    }

    public function getCountTransactions ()
    {
        $sqlstr = sprintf(
                "
        SELECT count(*)
        FROM
          transactions
      ");
        $query = $this->query($sqlstr);
        $array = mysql_fetch_row($query);
        return $array[0];
    }

    public function getTransactionItems ($transaction_id)
    {
        $sqlstr = sprintf(
                "
        SELECT 
          transaction_items.item
        FROM
          transaction_items
        WHERE
          transaction_items.transaction_id = %s

      ", $transaction_id);
        $query = $this->query($sqlstr);
        
        $items = null;
        while ($array = mysql_fetch_row($query, MYSQL_ASSOC)) {
            $items[] = $array['item'];
        }
        
        return $items;
    }

    public function getClusterItems ($cluster_id)
    {
        $sqlstr = sprintf(
                "
      SELECT
        transaction_items.item
      FROM
        transaction_items
        INNER JOIN cluster_transactions ON (transaction_items.transaction_id = cluster_transactions.transaction_id)
      WHERE
        cluster_transactions.cluster_id = %s
        ", $cluster_id);
        $query = $this->query($sqlstr);
        
        $rows = null;
        while ($array = mysql_fetch_row($query, MYSQL_ASSOC)) {
            $rows[] = $array['item'];
        }
        return $rows;
    }

    public function clusterNewAddTransaction ($transaction_id)
    {
        if (! $this->transactionIsFree($transaction_id)) {
            $this->transactionMakeFree($transaction_id);
        }
        
        $sqlstr = sprintf(
                "
      INSERT INTO
        clusters(
        id,
        height,
        width,
        size)
      VALUES(
        NULL,
        0,
        0,
        0)
      ");
        $this->query($sqlstr);
        
        $cluster_id = $this->last_insert_id();
        
        $this->clusterAddTransaction($cluster_id, $transaction_id);
        
        $this->clusterUpdateFeatures($cluster_id);
        
        return $cluster_id;
    }

    public function clusterExistAddTransaction ($cluster_id, $transaction_id)
    {
        if (! $this->clusterCheckTransactionExist($cluster_id, $transaction_id)) {
            $this->clusterAddTransaction($cluster_id, $transaction_id);
            $this->clusterUpdateFeatures($cluster_id);
        } else {
            throw new Exception('данная транзакция уже добавлена в кластер');
        }
        return $cluster_id;
    }

    public function clusterExistRemoveTransaction ($cluster_id, $transaction_id)
    {
        if ($this->clusterCheckTransactionExist($cluster_id, $transaction_id)) {
            $this->clusterRemoveTransaction($cluster_id, $transaction_id);
            if (! $this->clusterIsEmpty($cluster_id)) {
                $this->clusterUpdateFeatures($cluster_id);
            } else {
                $this->clusterDelete($cluster_id);
            }
        } else {
            throw new Exception('данная транзакция не найдена в кластере');
        }
        return $cluster_id;
    }

    public function clusterDeleteZerroClusters ()
    {
        $clusters = $this->getClusters();
        if (! is_null($clusters) && count($clusters) > 0) {
            foreach ($clusters as $cluster) {
                if ($this->clusterIsEmpty($cluster['id'])) {
                    $this->clusterDelete($cluster_id);
                }
            }
        }
    }

    public function clusterDelete ($cluster_id)
    {
        $sqlstr = sprintf(
                "
      DELETE
      FROM
        clusters
      WHERE
        clusters.id = %s
      ", $cluster_id);
        
        $this->query($sqlstr);
    }

    public function clusterIsEmpty ($cluster_id)
    {
        $sqlstr = sprintf(
                "
      SELECT 
        count(*)
      FROM
        cluster_transactions
      WHERE
        cluster_transactions.cluster_id = %s
      ", $cluster_id);
        $query = $this->query($sqlstr);
        $array = mysql_fetch_row($query);
        return ! (bool) $array[0];
    }

    public function clusterAddTransaction ($cluster_id, $transaction_id)
    {
        $sqlstr = sprintf(
                "
      INSERT INTO
        cluster_transactions(
        id,
        cluster_id,
        transaction_id)
      VALUES(
        NULL,
        %s,
        %s)
      ", $cluster_id, $transaction_id);
        $this->query($sqlstr);
    }

    public function clusterRemoveTransaction ($cluster_id, $transaction_id)
    {
        $sqlstr = sprintf(
                "
      DELETE
      FROM
        cluster_transactions
      WHERE
        cluster_transactions.cluster_id = %s AND 
        cluster_transactions.transaction_id = %s
      ", $cluster_id, $transaction_id);
        $this->query($sqlstr);
    }

    public function clusterCheckTransactionExist ($cluster_id, $transaction_id)
    {
        $sqlstr = sprintf(
                "
      SELECT COUNT(*)
        FROM
        cluster_transactions
      WHERE
        cluster_transactions.cluster_id = %s AND
        cluster_transactions.transaction_id = %s
      ", $cluster_id, $transaction_id);
        $query = $this->query($sqlstr);
        $array = mysql_fetch_row($query);
        return (bool) $array[0];
    }

    public function getCountClusters ()
    {
        $sqlstr = sprintf(
                "
        SELECT count(*)
        FROM
          clusters
      ");
        $query = $this->query($sqlstr);
        $array = mysql_fetch_row($query);
        return $array[0];
    }

    public function getClusters ()
    {
        $sqlstr = sprintf(
                "
        SELECT
          clusters.id,
          clusters.height,
          clusters.width,
          clusters.size
        FROM
          clusters
        ");
        $query = $this->query($sqlstr);
        
        $rows = null;
        while ($array = mysql_fetch_row($query, MYSQL_ASSOC)) {
            $rows[] = $array;
        }
        return $rows;
    }

    public function getTransactions ()
    {
        $sqlstr = sprintf(
                "
      SELECT 
        transactions.id,
        transactions.source_id
      FROM
        transactions
        ");
        $query = $this->query($sqlstr);
        
        $rows = null;
        while ($array = mysql_fetch_row($query, MYSQL_ASSOC)) {
            $rows[] = $array;
        }
        return $rows;
    }

    public function getCountTransactionsOfCluster ($cluster_id)
    {
        $sqlstr = sprintf(
                "
      SELECT 
        count(*)
      FROM
        cluster_transactions
      WHERE
        cluster_transactions.cluster_id = %s
      ", $cluster_id);
        $query = $this->query($sqlstr);
        $array = mysql_fetch_row($query);
        return $array[0];
    }

    public function getCluster ($cluster_id)
    {
        $sqlstr = sprintf(
                "
        SELECT 
          clusters.id,
          clusters.height,
          clusters.width
        FROM
          clusters
        WHERE
          clusters.id = %s     
        ", $cluster_id);
        $query = $this->query($sqlstr);
        
        $rows = null;
        while ($array = mysql_fetch_row($query, MYSQL_ASSOC)) {
            $rows[] = $array;
        }
        return $rows;
    }

    public function clusterUpdateFeatures ($cluster_id)
    {
        $items = $this->getClusterItems($cluster_id);
        
        $array_occ = array_count_values($items);
        arsort($array_occ);
        $w = count($array_occ);
        $s = 0;
        foreach ($array_occ as $elem => $value) {
            $s += $value;
        }
        $h = (float) $s / $w;
        
        $sqlstr = sprintf(
                "
      UPDATE
        clusters
      SET
        height = %s,
        width = %s,
        size = %s
      WHERE
        clusters.id = %s
      ", $h, $w, $s, $cluster_id);
        $this->query($sqlstr);
    }

    public function transactionIsFree ($transaction_id)
    {
        $sqlstr = sprintf(
                "
      SELECT
        count(*)
      FROM
        cluster_transactions
      WHERE
        cluster_transactions.transaction_id = %s
      ", $transaction_id);
        $query = $this->query($sqlstr);
        $array = mysql_fetch_row($query);
        return ! (bool) $array[0];
    }

    public function clusterTransactionMoveToCluster ($cluster_from, $cluster_to, 
            $transaction_id)
    {
        if (is_null($cluster_from)) {
            $this->transactionMakeFree($transaction_id);
            $this->clusterExistAddTransaction($cluster_to, $transaction_id);
        } else {
            if ($cluster_from != $cluster_to) {
                $this->clusterExistRemoveTransaction($cluster_from, 
                        $transaction_id);
                $this->clusterExistAddTransaction($cluster_to, $transaction_id);
            }
        }
    }

    public function transactionGetClusterId ($transaction_id)
    {
        $sqlstr = sprintf(
                "
      SELECT 
        cluster_transactions.cluster_id
      FROM
        cluster_transactions
      WHERE
        cluster_transactions.transaction_id = %s
      ", $transaction_id);
        $query = $this->query($sqlstr);
        $array = mysql_fetch_row($query);
        return $array[0];
    }

    public function transactionMakeFree ($transaction_id)
    {
        if (! $this->transactionIsFree($transaction_id)) {
            $cluster_id = $this->transactionGetClusterId($transaction_id);
            
            $this->clusterExistRemoveTransaction($cluster_id, $transaction_id);
        }
    }

    public function selectFromSources ()
    {
        $sqlstr = sprintf(
                "
      SELECT
        sources.id,
        sources.content
      FROM
        sources
      ");
        $query = $this->query($sqlstr);
        
        $rows = null;
        while ($array = mysql_fetch_row($query)) {
            $data[] = $array;
        }
        return $data;
    }

    function getClusterTransactions ($cluster_id)
    {
        $sqlstr = sprintf(
                "
      SELECT 
        cluster_transactions.transaction_id
      FROM
        cluster_transactions
      WHERE
        cluster_transactions.cluster_id = %s
      ", $cluster_id);
        $query = $this->query($sqlstr);
        $rows = null;
        while ($array = mysql_fetch_row($query, MYSQL_ASSOC)) {
            $rows[] = $array;
        }
        return $rows;
    }
}


