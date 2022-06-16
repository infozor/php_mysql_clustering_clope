<?php

namespace _Clope;

require_once '_Clope/ClopeDB.php';
use Exception;

class Clope
{
	protected $repulsion = 2.3;
	protected $f2_count_iterations = 3;
	protected $changes_issue = false;
	function __construct()
	{
		$this->ClopeDB = new ClopeDB();
		set_time_limit(0);
		$mem_limit = ( int ) ini_get('memory_limit');
		if ($mem_limit < 32)
		{
			ini_set('memory_limit', '32M');
		}
	}
	function clustering()
	{
		$this->ClopeDB->clusteringsEmpty();
		$transactions = $this->ClopeDB->getTransactions();
		
		foreach ( $transactions as $transaction )
		{
			$bestClusterId = $this->getBestClusterId($transaction, $this->repulsion);
			if (is_null($bestClusterId))
			{
				$this->cluster_id = $this->ClopeDB->clusterNewAddTransaction($transaction['id']);
			}
			else
			{
				$this->transactionMoveToCluster($transaction, $bestClusterId);
			}
		}
		
		$f2_iteration = 0;
		$moved = true;
		do
		{
			foreach ( $transactions as $transaction )
			{
				$bestClusterId = $this->getBestClusterId($transaction, $this->repulsion);
				if (is_null($bestClusterId))
				{
					$this->cluster_id = $this->ClopeDB->clusterNewAddTransaction($transaction['id']);
					$this->changes_issue = true;
				}
				else
				{
					$this->changes_issue = $this->transactionMoveToCluster($transaction, $bestClusterId);
				}
			}
			if ($this->changes_issue)
			{
				$moved = true;
			}
			else
			{
				$moved = false;
				echo $f2_iteration;
			}
			if ($f2_iteration > $this->f2_count_iterations)
			{
				$moved = false;
			}
			$f2_iteration++;
		}
		while ( $moved == true );
	}
	public function transactionMoveToCluster($transaction, $bestClusterId)
	{
		$clusterIdTo = $bestClusterId;
		$transactionId = $transaction['id'];
		
		$transactionIsFree = $this->ClopeDB->transactionIsFree($transaction['id']);
		if ($transactionIsFree)
		{
			$clusterIdFrom = null;
			$this->ClopeDB->clusterTransactionMoveToCluster($clusterIdFrom, $clusterIdTo, $transactionId);
			return true;
		}
		else
		{
			$clusterIdFrom = $this->ClopeDB->transactionGetClusterId($transaction['id']);
			if (!($clusterIdFrom == $clusterIdTo))
			{
				$this->ClopeDB->clusterTransactionMoveToCluster($clusterIdFrom, $clusterIdTo, $transactionId);
				return true;
			}
			else
			{
				return false;
			}
		}
	}
	public function getBestClusterId($transaction, $repulsion)
	{
		$clusters = $this->ClopeDB->getClusters();
		$delta = 0;
		$maxDelta = 0;
		
		$bestClusterId = null;
		if (!is_null($clusters))
		{
			foreach ( $clusters as $cluster )
			{
				if ($this->ClopeDB->clusterCheckTransactionExist($cluster['id'], $transaction['id']))
				{
					$delta = $this->deltaRemove($cluster, $transaction, $repulsion);
				}
				else
				{
					$delta = $this->deltaAdd($cluster, $transaction, $repulsion);
				}
				
				if ($delta >= $maxDelta)
				{
					$maxDelta = $delta;
					$bestClusterId = $cluster['id'];
				}
			}
		}
		return $bestClusterId;
	}
	public function deltaAdd($cluster, $transaction, $repulsion)
	{
		$transactionItems = $this->ClopeDB->getTransactionItems($transaction['id']);
		$clusterItems = $this->ClopeDB->getClusterItems($cluster['id']);
		
		$clusterCountTransactions = $this->ClopeDB->getCountTransactionsOfCluster($cluster['id']);
		$transactionExist = $this->ClopeDB->clusterCheckTransactionExist($cluster['id'], $transaction['id']);
		
		$transactionItemsCount = count($transactionItems);
		$sizeNew = $cluster['size'] + $transactionItemsCount;
		$widthNew = $cluster['width'];
		
		$commonItems = array_merge($transactionItems, $clusterItems);
		
		$commonItems_array_occ = array_count_values($commonItems);
		
		$widthNew = count($commonItems_array_occ);
		
		$paramNew = $sizeNew * ($clusterCountTransactions + 1) / pow($widthNew, $this->repulsion);
		$param = $cluster['size'] * $clusterCountTransactions / pow($cluster['width'], $this->repulsion);
		
		return $paramNew - $param;
		;
	}
	public function deltaRemove($cluster, $transaction, $repulsion)
	{
		$transactionItems = $this->ClopeDB->getTransactionItems($transaction['id']);
		$clusterItems = $this->ClopeDB->getClusterItems($cluster['id']);
		
		$clusterCountTransactions = $this->ClopeDB->getCountTransactionsOfCluster($cluster['id']);
		$transactionExist = $this->ClopeDB->clusterCheckTransactionExist($cluster['id'], $transaction['id']);
		
		$transactionItemsCount = count($transactionItems);
		$sizeNew = $cluster['size'] - $transactionItemsCount;
		$widthNew = $cluster['width'];
		
		if (count($clusterItems) > count($transactionItems))
		{
			$commonItems = array_diff_assoc($clusterItems, $transactionItems);
		}
		else
		{
			$commonItems = array_diff_assoc($transactionItems, $clusterItems);
		}
		
		$commonItems_array_occ = array_count_values($commonItems);
		
		$widthNew = count($commonItems_array_occ);
		
		if ($widthNew == 0)
		{
			$paramNew = 0;
		}
		else
		{
			$paramNew = $sizeNew * ($clusterCountTransactions + 1) / pow($widthNew, $this->repulsion);
		}
		
		$param = $cluster['size'] * $clusterCountTransactions / pow($cluster['width'], $this->repulsion);
		
		return $param - $paramNew;
	}
}