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
	const ACTION_BEGINTRANSACTION = 6;
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
		unset($this->strcut['where']);
		unset($this->strcut['action']);
		unset($this->strcut['order']);
		unset($this->strcut['group']);
		unset($this->strcut['having']);
		unset($this->strcut['selectFields']);
		unset($this->querySql);
		unset($this->strcut['data']);
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
		if (empty($arguments) || !is_array($arguments))	return $this;
		if (isset($this->querySql['input']) && is_array($this->querySql['input'])){
			$this->querySql['input'] = array_merge($this->querySql['input'],$arguments);
		}else{
			$this->querySql['input'] = $arguments;
		}
		return $this;
		
	}
    /**
     * 获取主键名称-	注意:一张表主键只认一个,即使是有多个存在,只认最前面一个
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
			$this->querySql = DriverAdapter::buildSql($this->strcut);
			$this->setFetchStyle();
			$this->fields['fields']  = DriverAdapter::getFields($this->querySql);
			$this->fields['_pk'] = $this->getPk($this->fields['fields']);
			//只用清楚$this->querySql即可，不然会对getall或getrow 判断是否为自定义querysql有影响
			unset($this->querySql);
		}
		return $this->fields;
		
	}
	/**
	 * 设置提取数据(getRow,getAll)的数据结构，此方法只支持pdo方式连接
	 * @param PDO $fetchStyle
	 * @param string $fetchArgument
	 * @param string $ctorArgs
	 * @return ModelBase
	 */
	private function setFetchStyle($fetchStyle = PDO::FETCH_ASSOC)
	{
		$this->querySql['fetchStyle'] = $fetchStyle;
		return $this;
	}
	/**
	 * 查询数据集-考虑到业务需要,所以查询分为查询数据集和查询数据行,查询数据行不是全查出来而后格式化数据,是调用api只查一行数据减小数据库压力和带宽
	 * @return array
	 */
	public function getAll($fetchStyle = PDO::FETCH_ASSOC)
	{
		$this->strcut['action'] = self::ACTION_SELECT;
		$this->strcut['tableName'] = $this->getTableName();
		//非自定义sql语句
		if (!isset($this->querySql['sql'])){
			//TODO::之所以考虑在这块拼凑sql语句，是因为只想让数据库驱动类包含getAll getRow exec 3个执行sql语句的方法，并且这两个方法不具备分析结构树的能力，也是为了开发人员直接能自定义sql语句做出铺垫,并且也符合php官方提供的api和简化数据库驱动编写
			$querySql = DriverAdapter::buildSql($this->strcut);
			//这里之所以再次拼凑$querySql 是为了合并上自定义的input 而不仅仅是分析出来的input
			$this->querySql['sql'] = $querySql['sql'];
			$this->input($querySql['input']);
		}
		$this->setFetchStyle($fetchStyle);
		$result  = DriverAdapter::getAll($this->querySql);
		dd($this->querySql,$result);
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
			if (isset($fields['_pk'])){
				$where[$fields['_pk']] = $pkValue;
				//把主键放在where条件最前面 优化sql语句
				if (!isset($this->strcut['where']))
					$this->strcut['where'] = array();
				$this->strcut['where'] = array_merge($where,$this->strcut['where']);
			}
		}
		$this->strcut['action'] = self::ACTION_SELECT;
		$this->strcut['tableName'] = $this->getTableName();
		//$this->strcut['limit'] = 'limit 1';
		if (!isset($this->querySql['sql'])){
			$querySql = DriverAdapter::buildSql($this->strcut);
			$this->querySql['sql'] = $querySql['sql'];
			$this->input($querySql['input']);
		}
		$this->setFetchStyle();
//		dd($this->querySql);
		$result  = DriverAdapter::getRow($this->querySql);
		//清空sql结构体
		$this->clearStruct();
		return $result;
	
	}
	/**
	 * insert操作
	 * @param array $data 数据源
	 * @return string 返回lastinsertid 数据表自增列的最后序号,如没有自增列(不一定要是主键)，则返回0,以此返回结果做判断的业务逻辑要注意
	 */
	public function insert($data)
	{
		$this->strcut['action'] = self::ACTION_INSER;
		$this->strcut['tableName'] = $this->getTableName();
		$this->strcut['data'] = $data;
		$querySql = DriverAdapter::buildSql($this->strcut);
		$this->querySql['sql'] = $querySql['sql'];
		$this->input($querySql['input']);
//		dd($this->querySql);
		$result  = DriverAdapter::insert($this->querySql);
		$this->clearStruct();
		return $result;
	}
	public function update($data)
	{
		//查找输入的字段是否有跟主键相同的字段名,有的话，作为条件存在，而不是作为set 值,当然有主键情况下，set整个表包括主键的值,mysql会报错，因为主键不允许重复
		$fields = $this->getFields();
		if (!empty($fields['_pk'])){
			foreach ($data as $filed=>$value){
				if (strtoupper($filed) == strtoupper($fields['_pk'])){
					$this->strcut['where'][$fields['_pk']] = $data[$filed];
					unset($data[$filed]);
					break;
				}
			}
		}
		$this->strcut['action'] = self::ACTION_UPDATE;
		$this->strcut['tableName'] = $this->getTableName();
		$this->strcut['data'] = $data;
		
		$querySql = DriverAdapter::buildSql($this->strcut);
		$this->querySql['sql'] = $querySql['sql'];
		$this->input($querySql['input']);
//		dd($this->querySql);
		$result  = DriverAdapter::update($this->querySql);
		$this->clearStruct();
		return $result;
	}
	public function delete($pkValue = '')
	{
		if (!empty($pkValue)){
			$fields = $this->getFields();
			if (!empty($fields['_pk'])){
				$where[$fields['_pk']] = $pkValue;
				//把主键放在where条件最前面 优化sql语句
				if (!isset($this->strcut['where']))
					$this->strcut['where'] = array();
				$this->strcut['where'] = array_merge($where,$this->strcut['where']);
			}
		}
		$this->strcut['action'] = self::ACTION_DELETE;
		$this->strcut['tableName'] = $this->getTableName();
		$querySql = DriverAdapter::buildSql($this->strcut);
		$this->querySql['sql'] = $querySql['sql'];
		$this->input($querySql['input']);
		$result  = DriverAdapter::delete($this->querySql);
		$this->clearStruct();
		return $result;
		
	}
	/**
	 * 事务处理-注意事务处理是针对于数据库而不是针对于表
	 */
	public function beginTransaction()
	{
		DriverAdapter::beginTransaction();
	
	}
	/**
	 * 提交事务
	 */
	public function commit()
	{
		DriverAdapter::commit();
	
	}
	/**
	 * 回滚事务
	 */
	public function rollback()
	{
		DriverAdapter::rollback();
	}
	public function query($querySql)
	{
		if (!empty($querySql))
			$this->querySql['sql'] = $querySql;
		if(!isset($this->querySql['input']))
			$this->querySql['input'] = array();
		return $this;
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
	/**
	 * 连表查询(包括外连，内连)  array(array('left'=>'user'),array('user.id'=>'think_test'.id))
	 * @param array $join
	 * @return ModelBase
	 */
	public function join($join)
	{
		$this->strcut['join'] = $join;
		return $this;
	}
	
	public function order($order)
	{
		$this->strcut['order'] = $order;
		return $this;
	}
	public function group($group)
	{
		$this->strcut['group'] = $group;
		return $this;
	}
	public function fields($fields)
	{
		$this->strcut['selectFields'] = $fields;
		return $this;
	
	}
	/**
	 * 由于having又相当于对group后的数据再进行where,情况比较复杂，所以暂时先只支持字符串方式
	 * @param string $having
	 * @return ModelBase
	 */
	public function having($having)
	{
		$this->strcut['having'] = $having;
		return $this;
	}
	/**
	 * limit array(1)|array(10,20) 规范化,即使是一个数字,也必须以结构化的方式传进来,不支持字符串各种形式
	 * @param array $limit
	 * @return ModelBase
	 */
	public function limit($limit)
	{
		if (empty($limit)) return $this;
		$this->strcut['limit']['limitOffset'] = $limit[0];
		if (isset($limit[1]))
			$this->strcut['limit']['limitCount'] = $limit[1];
		return $this;
	
	}
	/**
	 * 分页-注意第一页传1,请勿传0
	 * @param array $page array(1,20)
	 * @return ModelBase
	 */
	public function page($page)
	{
		if (empty($page)) return $this;
		$this->strcut['limit']['limitOffset'] = ($page[0]-1)*$page[1];
		$this->strcut['limit']['limitCount'] = $page[1];
		return $this;
	}
	
}