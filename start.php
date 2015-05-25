<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN""http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="shortcut icon" href="/images/favicon.ico"/>
</head>
<body bgcolor="#ffffff">
<?php
header('Content-Type: text/html; charset=utf-8');
date_default_timezone_set( 'Europe/Moscow' );
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(-1);
require_once '_Test/Test.php';

use _Test\Test;

$Test = new Test();
$Test->execute();