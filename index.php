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

try{
	
	$model->beginTransaction();
	$result = $model->delete(6);
			$model->commit();
	
}catch (ORMException $e){
	$model->rollback();
	echo $e->errorMessage();
}
dd($result);
//dd($result);


//$data['Id'] = array(array('egt',1),array('lt',3));
//$data['username'] = array('like',array('%shenpeng%','%shenpeng33'));
//$where['username'] = array(array('exp',' = :username'));
//$input[':username'] = "sss"; 
//$where['password'] = 7676;
////$where['ID'] = array(array('gt',1),array('lt',10));
//$where['_logic'] = array('or','or');
//$order = array('id'=>'desc');
////$order = 'id desc';
////$fields = 'username,id';
//$fields = array('count(username)'=>'um','password'=>'pw');
////$fields = array('username','password'=>'pw');
//$group = array('pw');
////$group = array('username','pw');
//$having = 'um >1';
//$limit = array(0,5);
////$where = array();
////$input = array(':aat'=>4);
////$data = $model->fields($fields)->where($where)->input($input)->order($order)->group($group)->having($having)->getAll(PDO::FETCH_ASSOC);
////$data = $model->where($where)->input($input)->limit($limit)->getAll();
//$data = $model->where($where)->input($input)->limit($limit)->getAll();
////$data = $model->query("select * from user LIMIT :aat")->input($input)->getAll();
//dd($data);

//join��ѯʱע��,�ر��ǵڶ����������������������ǵ�����
$join = array(array('left'=>'think_test','right'=>'category'),array('user.id'=>'think_test.id','category.id'=>'user.id'));

//$join = array(array('left'=>'think_test'),array('user.id'=>'think_test.id'));

//�����ѯ��һ��ע�ⲻͬ���е���ͬ�ֶγ�ͻ����,��where�����£��粻ͬ������ͬ�ֶΣ�һ��Ҫ����ǰ׺��������ѯʱҲҪע�⵽��ͬ�ֶλḲ�ǣ�Ҫ�����
$fields = array('user.id'=>'u_id','user.username'=>'u_username','think_test.key','title','summary');
//$fields = array('user.id'=>'u_id','user.username'=>'u_username','think_test.key','summary');

$order = array('u_id'=>'desc');
//�����ѯʱ��where�����������к�������ͬ�ֶ�,�����ñ�����ʽ����Ϊsql������Ƚ���where�־䣬��ʱ���ֶα�����������
$wheres['user.id'] = array('in',array(1,2,3,4));
$group = array('u_id');
$group = array('user.id');
//$wheres['summary'] = 'ss';
$limit = array(0,5);
$limit = array();
//һ�β�ѯ��limit��page����ֻ��һ����Ч���������Ч
$page = array(1,1);
//$data = $model->fields($fields)->join($join)->order($order)->where($wheres)->group($group)->limit($limit)->page($page)->getAll();
$model = new ModelBase('lastinsert','db');
$data['summ'] = '55';
//������С�Լ���
$data['aaa'] = 66;
//$data['key'] = 2;
//$result = $model->update($data);
//dd($result);
//$where['key'] = array(array('gt',3),array('lt',10));
$where['aaa'] = '6';
//$result = $model->where($where)->update($data);
//
//dd($model);
//$result = $model->delete(3);
$result = $model->where($where)->delete();
dd($result);



