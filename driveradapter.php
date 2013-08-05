<?php
/**
 * 适配器-适配数据库驱动类
 * 之所以把这个适配器设计为静态类,是为了在整个脚本执行周期内存储某些变量-所以以此来保证相同数据库链接只会拿一次数据库操作对象,在同一数据库连接下相同sql,直接返回缓存结构-以此来减少网络开销和mysql压力
 * 暴漏给外部的 操作数据库驱动方法只用简单的调用此类静态方法就ok,不用保存任何此类操作的结果集,此类会在脚本生命周期内,维护一切可能的数据库操作实例单列和sql结果集缓存
 * 此类承担角色-数据库驱动适配(选择的不同数据库链接方式，对应的不同数据库操作实现类),相同数据库连接配置对应数据库驱动类的实例-以此来保证数据库实例的单例,相同sql语句的结果集缓存
 * @author sp
 */
class DriverAdapter
{
	private static $db_driver = array();
	
	private static $connection;	//当前数据库的配置名
	
	private static $cache = array();
	
	private static $dbType;	//数据库连接方式，暂时只支持pdo
	
	
	public static function getInstance($connection)
	{
		//连接方式,暂时只支持PDO 
		$dbType = self::$dbType = 'PDO';
		$class = $dbType."Drive";
		
		self::$connection = $connection;
		
		$connectionConf = getConf($connection);
		//保证同一数据库配置 ，数据库驱动的单例
		if (!isset(self::$db_driver[$connection]) || empty(self::$db_driver[$connection])){
			self::$db_driver[$connection] = new $class($connectionConf);
		}
	}
	/**
	 * 调用具体数据库驱动类拼凑sql-之所以单独提出来,是因为保证_call方法能够正常缓存结果集而不是生成的sqlquery
	 * @param Object $strcut 对象结构
	 * @return sqlQuery
	 */
	public static function buildSql($strcut)
	{
		if (isset(self::$db_driver[self::$connection]))
			return self::$db_driver[self::$connection]->buildSql($strcut);
	
	}
	/**
	 * 生成缓存key
	 * @param array $querySql 查询语句
	 * @param String $connection 数据库链接串
	 * @return String
	 */
	private static function getCacheKey($querySql,$connection)
	{
		$specialArgs = isset($querySql['fetchStyle'])?$querySql['fetchStyle']:'';
		$input = isset($querySql['input'])?implode('', $querySql['input']):'';
		$cacheKey = md5($querySql['sql'].$input.$connection.$specialArgs);
		return $cacheKey;
	
	}	
	/**
	 * 调用具体数据库驱动类来执行具体的sql语句-脚本执行生命周期缓存逻辑
	 * @param string $fnName
	 * @param obecject $arguments
	 */
	public static function __callStatic($fnName,$arguments)
	{
		if (!isset(self::$db_driver[self::$connection]))	return;
		//兼容不需要sql语句就能操作数据库对象的方法,begintransaction commit rollback
		if(empty($arguments))
			return self::$db_driver[self::$connection]->$fnName();
			
		$querySql = $arguments[0];
		$cacheKey = self::getCacheKey($querySql,self::$connection);
		if (isset(self::$cache[$cacheKey]))
			return	self::$cache[$cacheKey];
		self::$cache[$cacheKey] = self::$db_driver[self::$connection]->$fnName($querySql);
		return self::$cache[$cacheKey];
	
	}
	
	
	
}