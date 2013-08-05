<?php
/**
 * ���ݿ������� --��������tree��ƴװsql ����ִ��sql���
 * @author sp
 *
 */
class PDODrive extends ParseSql implements Adrive
{
	private $db_master_link;	//�����ݿ�����
	
	private $db_slave_link;		//�����ݿ�����
	
	private $connection;	//���ݿ����Ӵ�
	
	private $is_ms_link	= FALSE;	//�Ƿ�Ϊ���Ӽܹ�,Ŀǰ��֧�����Ӽܹ�
	
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
	 * ���ݿ����� - ����ִ��sqlʱ�Ż��������ݿ� ��ִ�б���query��exec�ŵ���
	 */
	private function initConnect()
	{
		if (!isset($this->db_master_link))
			$this->db_master_link = new PDO($this->connection['db_dns'], $this->connection['db_username'], $this->connection['db_passwd']);
		
		if (!isset($this->db_slave_link) && $this->is_ms_link === TRUE)
			$this->db_slave_link = new PDO($this->connection['db_dns'], $this->connection['db_username'], $this->connection['db_passwd']);
	}
	
	/**
	 * ����sql�ṹ������sql���
	 * @param Object $struct �ṹ��
	 * @return array $sql sql��ѯ�ṹ,����sql���ͷ��������Ĵ��滻����
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
		//����������input����
		$querySql['input'] = $this->querySql_input;
		return $querySql;
	}
	/**
	 * ����ռλ��ֵ�ľ�������,��ȷ��bindParams����
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
	 * �����޽�������ݿ����(insert/update/delete)����������
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
		//TODO:throw sql�﷨�쳣 ,��㼯�в���,���������ݷ���ֵ�߼��ж�,��ߴ���������,�����ܹ���ֹ�������ɢ,�����벶���,�����ֲ�,һ���������һ���ã������rollback��������֤���ݵ�һ����
		//TODO:��Ҫע����� ֻ��˵sql�﷨����execute()�ŷ���false����throw�쳣�������û���������ݣ�����0����array()����һ����ȷ�Ľ��,�����쳣
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
	 * ��������
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
	 * �ύ����
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
	 * �ع�����
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
			//lastInsertId ����������������ӵ�ֵ
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
	 * ��ѯ�����
	 * @param Array $querySql ��ѯ�ṹ
	 * @return array 
	 * @throws ORMException ������쳣throw �Զ����쳣��  -֮�����Զ����쳣��1.��ֹpdo throw�쳣��ֱ�Ӱ����Ӵ���ӡ���� 2. Ϊ���ڼ��д���ʱ�ܸ��õ�����
	 * @todo:�쳣���ƿ��Լ��ٽ��ҵ���߼����ж�,���ִ��������,������ҵ���߼����������ͱ�֤���ݵ�һ���ԣ����������Բ����쳣,�������쳣��Ӱ�췶Χ
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
	 * ��ѯ�����
	 * @param Array $querySql ��ѯ�ṹ
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