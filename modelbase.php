<?php
class ModelBase
{
	private $strcut;		//sql数组化结构体
	
	private $querySql;		//可查询化sql语句,只有自定义sql语句,使用query才有值,不算做sql结构树,因为struct分析完后才得到querysql querySql不需要经过bulidsql 所以单独拿出来做一个属性
	
	private $fields;			//操作的表的字段名
	
	
	//sql标识
	const ACTION_SELECT = 1;
	const ACTION_INSER = 2;
	const ACTION_UPDATE = 3;
	const ACTION_DELETE = 4;
	const ACTION_SHOW_COLUMS = 5;
	/**
	 * 初始化数据库访问实体 -直接暴漏给开发人员,可以在不自定义业务model的情况下，直接new次基类,减少开发人员编码成本
	 * @param string $name model名称-同时也是数据表所对应的名称
	 * @param string $connection  数据库连接串-可以
	 */
	public function __construct($tableName='',$connection='')
	{
		$connection = !empty($connection)?$connection:$this->connection;
		$this->setConnection($connection);
		if (empty($tableName)){
			$callerClass = get_class($this);
			if ($callerClass != __CLASS__ && !isset($this->tableName)){
				$this->strcut['tableName'] = $callerClass;
			}elseif (isset($this->tableName)){
				$this->strcut['tableName'] = $this->tableName;
			}
			
		}
		else{
			$this->strcut['tableName'] = $tableName;
		}
			
	
	}
	/**
	 * 清空sql结构体,保证下次查询结构树
	 */
	private function clearStruct()
	{
		$this->strcut['where'] = NULL;
		$this->strcut['action'] = NULL;
	
	}
	/**
	 * 设置数据库连接字符串-公有方法,开发人员完全可以实例化空model基类后,在调用此方法进行连接字符串设置
	 * @param string|array $connection 连接串
	 */
	public function setConnection($connection)
	{
		if (!empty($connection))
			DriverAdapter::getInstance($connection);
	}
	/**
	 * 获取操作的表
	 */
	private function getTableName()
	{
		return $this->strcut['tableName'];
	}
	/**
	 * 设置操作的表-暴漏给开发人员,可以根据业务设置表名
	 * @param string $tableName 表名
	 */
	public function setTableName($tableName)
	{
		if (!empty($tableName))
			$this->strcut['tableName'] = $tableName;
	
	}
	/**
	 * 设置待转义参数
	 * @param array $arguments
	 * @return this
	 */
	public function input($arguments)
	{
		if (empty($arguments) || !is_array($arguments))	return;
		$this->strcut['input'] = $arguments;
		return $this;
		
	}
    /**
     * 获取主键名称
     * @access public
     * @return string
     */
    public function getPk($fields)
    {
    	foreach ($fields as $key=>$value){
    		if ($value['primary'] == TRUE){
    			return $key;
    		}
    	}
        return '';
    }
	private function getFields()
	{
		if(!isset($this->fields)){
			$this->strcut['action'] = self::ACTION_SHOW_COLUMS;
			$this->strcut['tableName'] = $this->getTableName();
			$querySql = DriverAdapter::buildSql($this->strcut);
			$this->fields['fields']  = DriverAdapter::getFields($querySql);
			$this->fields['_pk'] = $this->getPk($this->fields['fields']);
		}
		return $this->fields;
		
	}
	/**
	 * 查询数据集-考虑到业务需要,所以查询分为查询数据集和查询数据行,查询数据行不是全查出来而后格式化数据,是调用api只查一行数据减小数据库压力和带宽
	 * @return array
	 */
	public function getAll()
	{
		$this->strcut['action'] = self::ACTION_SELECT;
		$this->strcut['tableName'] = $this->getTableName();
		//TODO::之所以考虑在这块拼凑sql语句，是因为只想让数据库驱动类包含getAll getRow exec 3个执行sql语句的方法，并且这两个方法不具备分析结构树的能力，也是为了开发人员直接能自定义sql语句做出铺垫,并且也符合php官方提供的api和简化数据库驱动编写
		if (!isset($this->querySql)){
			$querySql = DriverAdapter::buildSql($this->strcut);
		}
		else{
			$querySql = $this->querySql;
		}
		$result  = DriverAdapter::getAll($querySql);
		//清空sql结构体
		$this->clearStruct();
		return $result;
	}
	/**
	 * 查询数据行-查询数据行不是全查出来而后格式化数据,是调用api只查一行数据减小数据库压力和带宽
	 */
	public function getRow($pkValue = '')
	{
		if (!empty($pkValue)){
			$fields = $this->getFields();
			$where[$fields['_pk']] = $pkValue;
			//把主键放在where条件最前面 优化sql语句
			if (!isset($this->strcut['where']))
				$this->strcut['where'] = array();
			$this->strcut['where'] = array_merge($where,$this->strcut['where']);
		}
		$this->strcut['action'] = self::ACTION_SELECT;
		$this->strcut['tableName'] = $this->getTableName();
		//$this->strcut['limit'] = 'limit 1';
		if (!isset($this->querySql)){
			$querySql = DriverAdapter::buildSql($this->strcut);
		}
		else{
			$querySql = $this->querySql;
		}
//		dd($querySql);
		$result  = DriverAdapter::getRow($querySql);
		//清空sql结构体
		$this->clearStruct();
		return $result;
	
	}
	public function query($querySql)
	{
		if (!empty($querySql) && is_array($querySql))
			$this->querySql = $querySql;
		return $this;
	}
	public function insert($fields)
	{
		
	}
	public function delete()
	{

	}
	public function where($condition)
	{
		if(isset($this->strcut['where'])){
            $this->strcut['where'] = array_merge($this->strcut['where'],$condition);
        }else{
            $this->strcut['where'] = $condition;
        }
//        var_dump($this->strcut,$condition);
		return $this;
		
	}
	public function order($params)
	{
		
	}
	
	
}