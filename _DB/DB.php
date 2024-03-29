<?php

namespace _DB;

use Exception;
use ErrorException;

class DB
{
	protected static $instance = NULL;
	public static function &instance()
	{
		empty(self::$instance) and new DB();
		return self::$instance;
	}
	function __construct()
	{
		set_error_handler(function ($errno, $errstr, $errfile, $errline, array $errcontext)
		{
			if (0 === error_reporting())
			{
				return false;
			}
			throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
		});
		
		$this->db_server = 'localhost';
		$this->db_login = 'clope';
		$this->db_name = 'clope';
		$this->db_password = 'clope';
		$this->Connect();
	}
	function Connect()
	{
		if (self::$instance === NULL)
		{
			try
			{
				$this->link = mysqli_connect($this->db_server, $this->db_login, $this->db_password, $this->db_name);
				$result = mysqli_select_db($this->link, $this->db_name);
				if (!$result)
				{
					throw new Exception(mysqli_error());
				}
				self::$instance = $this->link;
			}
			catch ( Exception $e )
			{
				die($e);
			}
		}
		else
		{
			$this->link = self::$instance;
		}
	}
	public static function close_connection()
	{
		if (!empty(self::$instance))
		{
			mysqli_close(self::$instance);
			self::$instance = NULL;
		}
	}
	function last_insert_id()
	{
		return mysqli_insert_id($this->link);
	}
	function query($sqlstr)
	{
		try
		{
			$result = mysqli_query($this->link, $sqlstr);
			if (!$result)
			{
				throw new Exception(mysqli_error());
			}
		}
		
		catch ( Exception $e )
		{
			die($e);
		}
		return $result;
	}
}
