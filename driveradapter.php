<?php
/**
 * ������-�������ݿ�������
 * ֮���԰�������������Ϊ��̬��,��Ϊ���������ű�ִ�������ڴ洢ĳЩ����-�����Դ�����֤��ͬ���ݿ�����ֻ����һ�����ݿ��������,��ͬһ���ݿ���������ͬsql,ֱ�ӷ��ػ���ṹ-�Դ����������翪����mysqlѹ��
 * ��©���ⲿ�� �������ݿ���������ֻ�ü򵥵ĵ��ô��ྲ̬������ok,���ñ����κδ�������Ľ����,������ڽű�����������,ά��һ�п��ܵ����ݿ����ʵ�����к�sql���������
 * ����е���ɫ-���ݿ���������(ѡ��Ĳ�ͬ���ݿ����ӷ�ʽ����Ӧ�Ĳ�ͬ���ݿ����ʵ����),��ͬ���ݿ��������ö�Ӧ���ݿ��������ʵ��-�Դ�����֤���ݿ�ʵ���ĵ���,��ͬsql���Ľ��������
 * @author sp
 */
class DriverAdapter
{
	private static $db_driver = array();
	
	private static $connection;	//��ǰ���ݿ��������
	
	private static $cache = array();
	
	
	public static function getInstance($connection)
	{
		//���ӷ�ʽ,��ʱֻ֧��PDO 
		$dbType = 'PDO';
		$class = $dbType."Drive";
		
		self::$connection = $connection;
		
		$connectionConf = getConf($connection);
		//��֤ͬһ���ݿ����� �����ݿ������ĵ���
		if (!isset(self::$db_driver[$connection]) || empty(self::$db_driver[$connection])){
			self::$db_driver[$connection] = new $class($connectionConf);
		}
	}
	/**
	 * ���þ������ݿ�������ƴ��sql-֮���Ե��������,����Ϊ��֤_call�����ܹ����������������������ɵ�sqlquery
	 * @param Object $strcut ����ṹ
	 * @return sqlQuery
	 */
	public static function buildSql($strcut)
	{
		if (isset(self::$db_driver[self::$connection]))
			return self::$db_driver[self::$connection]->buildSql($strcut);
	
	}
	/**
	 * ���ɻ���key
	 * @param array $querySql ��ѯ���
	 * @param String $connection ���ݿ����Ӵ�
	 * @return String
	 */
	private static function getCacheKey($querySql,$connection)
	{
		$cacheKey = md5($querySql['sql'].implode('', $querySql['input']).$connection);
		return $cacheKey;
	
	}	
	/**
	 * ���þ������ݿ���������ִ�о����sql���-�ű�ִ���������ڻ����߼�
	 * @param string $fnName
	 * @param obecject $arguments
	 */
	public static function __callStatic($fnName,$arguments)
	{
		if (!isset(self::$db_driver[self::$connection]))	return;
		
		$querySql = $arguments[0];
		$cacheKey = self::getCacheKey($querySql,self::$connection);
		if (isset(self::$cache[$cacheKey]))
			return	self::$cache[$cacheKey];
		self::$cache[$cacheKey] = self::$db_driver[self::$connection]->$fnName($querySql);
		return self::$cache[$cacheKey];
	
	}
	
	
	
}