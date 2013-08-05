<?php
function autoload($className)
{
	$filename = APPDIR.strtolower($className).".php";
	require_once $filename;

}
function getConf($key)
{
	global $config;
	if (isset($config[$key])){
		return $config[$key];
	}else{
		return array();
	}
}
function dd()
{
	$arr = func_get_args();
    echo '<pre>';
	foreach($arr as $v){
		var_dump($v);
	}
    echo '</pre>';
	exit();

}
function writeLog($e)
{
	

}