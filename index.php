<?php
error_reporting(E_ALL | E_STRICT);
define('APPDIR', __DIR__.'/');
require APPDIR.'myphp.php';


//$model = new ModelBase();
//$model->setConnection('db');
//$querySql['sql'] = "select * from think_test where Id =:Id";
//$querySql['input'] = array(':Id'=>2);
//$data = $model->query($querySql)->getAll();
////$model->getRow(2);
////$model->getRow(2);
////$model->getRow(2);
////$model->getRow(1);
////$model->getRow(0);
//dd($data);


//$model = new think_test();
//$model->setTableName('user');
//$model->setConnection('db_test2');
//
//dd($model->getTop2());
$model = new ModelBase('user','db');

//$data['Id'] = array(array('egt',1),array('lt',3));
//$data['username'] = array('like',array('%shenpeng%','%shenpeng33'));
$fields['username'] = array(array('exp',' = :username'));
$input[':username'] = "sss"; 
$data = $model->where($fields)->input($input)->getAll();
$data = $model->where($fields)->input($input)->getAll();
$data = $model->where($fields)->input($input)->getAll();
dd($data);
