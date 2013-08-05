<?php
/**
 * 数据库驱动类 --分析对象tree，拼装sql 最终执行sql语句
 * @author sp
 *
 */
class PDODrive extends ParseSql implements Adrive
{
	private $db_master_link;	//主数据库连接
	
	private $db_slave_link;		//从数据库连接
	
	private $connection;	//数据库连接串
	
	private $is_ms_link	= FALSE;	//是否为主从架构,目前不支持主从架构
	
	private $sql_select_tpl = 'SELECT%DISTINCT% %FIELD% FROM %TABLE%%JOIN%%WHERE%%GROUP%%HAVING%%ORDER%%LIMIT%%UNION%%COMMENT%';
	
	private $sql_insert_tpl = 'INSERT INTO %TABLE% %DATA%';
	
	private $sql_update_tpl = 'UPDATE %TABLE% %DATA%%WHERE%';
	
	private $sql_delete_tpl = 'DELETE FROM %TABLE%%WHERE%';
	
	private $sql_show_colums_tpl = 'SHOW COLUMNS FROM %TABLE%';
	
	
	
	
	
	public function __construct($connection)
	{
		$this->connection = $connection;
		$this->is_ms_link = FALSE;
	
	}
	
	/**
	 * 数据库连接 - 仅当执行sql时才会连接数据库 即执行本类query、exec才调用
	 */
	private function initConnect()
	{
		if (!isset($this->db_master_link))
			$this->db_master_link = new PDO($this->connection['db_dns'], $this->connection['db_username'], $this->connection['db_passwd']);
		
		if (!isset($this->db_slave_link) && $this->is_ms_link === TRUE)
			$this->db_slave_link = new PDO($this->connection['db_dns'], $this->connection['db_username'], $this->connection['db_passwd']);
	}
	
	/**
	 * 根据sql结构树构建sql语句
	 * @param Object $struct 结构树
	 * @return array $sql sql查询结构,包含sql语句和分析出来的待替换参数
	 */
	public function buildSql($struct)
	{
		$querySql = array();
		if ($struct['action'] == ModelBase::ACTION_SELECT)
		{
			$querySql['sql'] = str_replace(
	            array('%TABLE%','%DISTINCT%','%FIELD%','%JOIN%','%WHERE%','%GROUP%','%HAVING%','%ORDER%','%LIMIT%','%UNION%','%COMMENT%'),
	            array(
	                isset($struct['tableName'])?$struct['tableName']:'',
	                isset($struct['distinct'])?$struct['distinct']:'',
	                isset($struct['selectFields'])?$this->__parseSqlFields($struct['selectFields']):'*',
	                isset($struct['join'])?$this->__parseSqlJoin($struct['join']):'',
	                $this->__parseSqlWhere(isset($struct['where'])?$struct['where']:NULL),
	                isset($struct['group'])?$this->__parseSqlGroup($struct['group']):'',
	                isset($struct['having'])?$this->__parseSqlHaving($struct['having']):'',
	                isset($struct['order'])?$this->__parseSqlOrder($struct['order']):'',
	                isset($struct['limit'])?$this->__parseSqlLimit($struct['limit']):'',
	                isset($struct['union'])?$struct['union']:'',
	                isset($struct['comment'])?$struct['comment']:''
	                ),
	             $this->sql_select_tpl);
			
		}elseif ($struct['action'] == ModelBase::ACTION_INSER){
			$querySql['sql'] = str_replace(
				array('%TABLE%','%DATA%'), 
				array(
					isset($struct['tableName'])?$struct['tableName']:'',
					isset($struct['data'])?$this->__parseSqlInsertData($struct['data']):'',
				),
				$this->sql_insert_tpl);
		
		}elseif ($struct['action'] == ModelBase::ACTION_DELETE){
			$querySql['sql'] = str_replace(
				array('%TABLE%','%WHERE%'), 
				array(
					isset($struct['tableName'])?$struct['tableName']:'',
					isset($struct['where'])?$this->__parseSqlWhere($struct['where']):'',
				),
				$this->sql_delete_tpl);
		}elseif ($struct['action'] == ModelBase::ACTION_UPDATE){
			$querySql['sql'] = str_replace(
				array('%TABLE%','%DATA%','%WHERE%'), 
				array(
					isset($struct['tableName'])?$struct['tableName']:'',
					isset($struct['data'])?$this->__parseSqlUpdateData($struct['data']):'',
					isset($struct['where'])?$this->__parseSqlWhere($struct['where']):'',
				),
				$this->sql_update_tpl);
		
		}elseif ($struct['action'] == ModelBase::ACTION_SHOW_COLUMS){
			$querySql['sql'] = str_replace('%TABLE%', isset($struct['tableName'])?$struct['tableName']:'', $this->sql_show_colums_tpl);
		}
		//分析出来的input返回
		$querySql['input'] = $this->querySql_input;
		return $querySql;
	}
	/**
	 * 根据占位符值的具体类型,来确定bindParams类型
	 * @param mix $fieldValue
	 * @return PDO
	 */
	private function getPDOParamType($fieldValue)
	{
		$pdoParamType = PDO::PARAM_STR;		
		if (is_bool($fieldValue)){
			$pdoParamType = PDO::PARAM_BOOL;
		}elseif (is_int($fieldValue)) {
			$pdoParamType = PDO::PARAM_INT;
		}elseif(is_null($fieldValue)){
			$pdoParamType =PDO::PARAM_NULL;
		}
		return $pdoParamType;
	}
	private function query($querySql)
	{
		$this->initConnect();
		$dbLink = $this->is_ms_link?$this->db_slave_link:$this->db_master_link;
		$sth = $dbLink->prepare($querySql['sql']);
		
		if (isset($querySql['input']) && is_array($querySql['input'])){
			foreach ($querySql['input'] as $field=>$fieldValue){
				$type = $this->getPDOParamType($fieldValue);
				$sth->bindValue($field, $fieldValue,$type);
			}
		}
		$sth->execute();
		return $sth;
	}
	/**
	 * 用于无结果集数据库操作(insert/update/delete)作用于主库
	 * @param array $querySql
	 * @return PDO
	 */
	private function exec($querySql)
	{
		$this->initConnect();
		$dbLink = $this->db_master_link;
		
		$sth = $dbLink->prepare($querySql['sql']);
		if (isset($querySql['input']) && !empty($querySql['input'])){
			foreach ($querySql['input'] as $field=>$fieldValue){
				$type = $this->getPDOParamType($fieldValue);
				$sth->bindValue($field, $fieldValue,$type);
			}
		}
		//TODO:throw sql语法异常 ,外层集中捕获,减少外层根据返回值逻辑判断,提高代码优雅性,并且能够防止错误的扩散,甚至与捕获后,可以弥补,一般跟事务处理一起用，捕获后rollback整个事务保证数据的一致性
		//TODO:需要注意的是 只有说sql语法错误execute()才返回false才能throw异常，如果是没有这条数据，返回0或者array()这是一个正确的结果,而非异常
		if ($sth->execute() === FALSE){
			if(isset($querySql['input']) && !empty($querySql['input'])){
				$inputKeys = array_keys($querySql['input']);
				$inputValues = array_values($querySql['input']);
				$sql = str_replace($inputKeys, $inputValues, $querySql['sql']);
			}else{
				$sql = $querySql['sql'];
			}
			throw new ORMException("sql syntax error : ".$sql);
		}
		return $sth;
	
	}
	/**
	 * 开启事务
	 */
	public function beginTransaction()
	{
		try {
			$this->initConnect();
			$dbLink = $this->db_master_link;
			$dbLink->beginTransaction();
		}catch (PDOException $e){
			writeLog($e);
			throw new ORMException($e->getMessage());
		}	
	}
	/**
	 * 提交事务
	 */
	public function commit()
	{
		try {
			$this->initConnect();
			$dbLink = $this->db_master_link;
			$dbLink->commit();
		}catch (PDOException $e){
			writeLog($e);
			throw new ORMException($e->getMessage());
		}
	}
	/**
	 * 回滚事务
	 */
	public function rollback()
	{
		try {
			$this->initConnect();
			$dbLink = $this->db_master_link;
			$dbLink->rollback();
		}catch (PDOException $e){
			writeLog($e);
			throw new ORMException($e->getMessage());
		}
	}
	public function insert($querySql)
	{
		try {
			$sth = $this->exec($querySql);
			//lastInsertId 返回自增列最后增加的值
			return $this->db_master_link->lastInsertId();
		}catch (PDOException $e){
			writeLog($e);
			throw new ORMException($e->getMessage());
		}
	}
	public function update($querySql)
	{
		try {
			$sth = $this->exec($querySql);
			return $sth->rowCount();
		}catch (PDOException $e){
			writeLog($e);
			throw new ORMException($e->getMessage());
		}
	}
	public function delete($querySql)
	{
		try {
			$sth = $this->exec($querySql);
			return $sth->rowCount();
		}catch (PDOException $e){
			writeLog($e);
			throw new ORMException($e->getMessage());
		}
	}

	public function getFields($querySql)
	{
		$result = $this->getAll($querySql);
		$info   =   array();
        if($result) {
            foreach ($result as $key => $val) {
                $info[$val['Field']] = array(
                    'name'    => $val['Field'],
                    'type'    => $val['Type'],
                    'notnull' => (bool) (strtoupper($val['Null']) === 'NO'), // not null is empty, null is yes
                    'default' => $val['Default'],
                    'primary' => (strtolower($val['Key']) == 'pri'),
                    'autoinc' => (strtolower($val['Extra']) == 'auto_increment'),
                );
            }
        }
        return $info;
	
	}
	/**
	 * 查询结果集
	 * @param Array $querySql 查询结构
	 * @return array 
	 * @throws ORMException 如出现异常throw 自定义异常类  -之所以自定义异常，1.防止pdo throw异常会直接把连接串打印出来 2. 为了在集中处理时能更好的区分
	 * @todo:异常机制可以减少解决业务逻辑的判断,保持代码的优雅,有利于业务逻辑的事务处理，和保证数据的一致性，你甚至可以捕获异常,来减少异常的影响范围
	 */
	public function getAll($querySql)
	{
		try{
			$sth = $this->query($querySql);
			return $sth->fetchAll($querySql['fetchStyle']);
		}catch (PDOException $e){
			writeLog($e);
			throw new ORMException($e->getMessage());
		}
	}
	/**
	 * 查询结果行
	 * @param Array $querySql 查询结构
	 * @return array 
	 * @throws ORMException
	 */
	public function getRow($querySql)
	{
		try{
			$sth = $this->query($querySql);
			return $sth->fetch($querySql['fetchStyle']);;
		}catch (PDOException $e){
			writeLog($e);
			throw new ORMException($e->getMessage());
		}
	}



}