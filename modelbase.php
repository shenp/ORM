<?php
class ModelBase
{
	private $strcut;		//sql���黯�ṹ��
	
	private $querySql;		//�ɲ�ѯ��sql���,ֻ���Զ���sql���,ʹ��query����ֵ,������sql�ṹ��,��Ϊstruct�������ŵõ�querysql querySql����Ҫ����bulidsql ���Ե����ó�����һ������
	
	private $fields;			//�����ı���ֶ���
	
	
	//sql��ʶ
	const ACTION_SELECT = 1;
	const ACTION_INSER = 2;
	const ACTION_UPDATE = 3;
	const ACTION_DELETE = 4;
	const ACTION_SHOW_COLUMS = 5;
	const ACTION_BEGINTRANSACTION = 6;
	/**
	 * ��ʼ�����ݿ����ʵ�� -ֱ�ӱ�©��������Ա,�����ڲ��Զ���ҵ��model������£�ֱ��new�λ���,���ٿ�����Ա����ɱ�
	 * @param string $name model����-ͬʱҲ�����ݱ�����Ӧ������
	 * @param string $connection  ���ݿ����Ӵ�-����
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
	 * ���sql�ṹ��,��֤�´β�ѯ�ṹ��
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
	 * �������ݿ������ַ���-���з���,������Ա��ȫ����ʵ������model�����,�ڵ��ô˷������������ַ�������
	 * @param string|array $connection ���Ӵ�
	 */
	public function setConnection($connection)
	{
		if (!empty($connection))
			DriverAdapter::getInstance($connection);
	}
	/**
	 * ��ȡ�����ı�
	 */
	private function getTableName()
	{
		return $this->strcut['tableName'];
	}
	/**
	 * ���ò����ı�-��©��������Ա,���Ը���ҵ�����ñ���
	 * @param string $tableName ����
	 */
	public function setTableName($tableName)
	{
		if (!empty($tableName))
			$this->strcut['tableName'] = $tableName;
	
	}
	/**
	 * ���ô�ת�����
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
     * ��ȡ��������-	ע��:һ�ű�����ֻ��һ��,��ʹ���ж������,ֻ����ǰ��һ��
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
			//ֻ�����$this->querySql���ɣ���Ȼ���getall��getrow �ж��Ƿ�Ϊ�Զ���querysql��Ӱ��
			unset($this->querySql);
		}
		return $this->fields;
		
	}
	/**
	 * ������ȡ����(getRow,getAll)�����ݽṹ���˷���ֻ֧��pdo��ʽ����
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
	 * ��ѯ���ݼ�-���ǵ�ҵ����Ҫ,���Բ�ѯ��Ϊ��ѯ���ݼ��Ͳ�ѯ������,��ѯ�����в���ȫ����������ʽ������,�ǵ���apiֻ��һ�����ݼ�С���ݿ�ѹ���ʹ���
	 * @return array
	 */
	public function getAll($fetchStyle = PDO::FETCH_ASSOC)
	{
		$this->strcut['action'] = self::ACTION_SELECT;
		$this->strcut['tableName'] = $this->getTableName();
		//���Զ���sql���
		if (!isset($this->querySql['sql'])){
			//TODO::֮���Կ��������ƴ��sql��䣬����Ϊֻ�������ݿ����������getAll getRow exec 3��ִ��sql���ķ����������������������߱������ṹ����������Ҳ��Ϊ�˿�����Աֱ�����Զ���sql��������̵�,����Ҳ����php�ٷ��ṩ��api�ͼ����ݿ�������д
			$querySql = DriverAdapter::buildSql($this->strcut);
			//����֮�����ٴ�ƴ��$querySql ��Ϊ�˺ϲ����Զ����input ���������Ƿ���������input
			$this->querySql['sql'] = $querySql['sql'];
			$this->input($querySql['input']);
		}
		$this->setFetchStyle($fetchStyle);
		$result  = DriverAdapter::getAll($this->querySql);
		dd($this->querySql,$result);
		//���sql�ṹ��
		$this->clearStruct();
		return $result;
	}
	/**
	 * ��ѯ������-��ѯ�����в���ȫ����������ʽ������,�ǵ���apiֻ��һ�����ݼ�С���ݿ�ѹ���ʹ���
	 */
	public function getRow($pkValue = '')
	{
		if (!empty($pkValue)){
			$fields = $this->getFields();
			if (isset($fields['_pk'])){
				$where[$fields['_pk']] = $pkValue;
				//����������where������ǰ�� �Ż�sql���
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
		//���sql�ṹ��
		$this->clearStruct();
		return $result;
	
	}
	/**
	 * insert����
	 * @param array $data ����Դ
	 * @return string ����lastinsertid ���ݱ������е�������,��û��������(��һ��Ҫ������)���򷵻�0,�Դ˷��ؽ�����жϵ�ҵ���߼�Ҫע��
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
		//����������ֶ��Ƿ��и�������ͬ���ֶ���,�еĻ�����Ϊ�������ڣ���������Ϊset ֵ,��Ȼ����������£�set���������������ֵ,mysql�ᱨ����Ϊ�����������ظ�
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
				//����������where������ǰ�� �Ż�sql���
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
	 * ������-ע������������������ݿ����������ڱ�
	 */
	public function beginTransaction()
	{
		DriverAdapter::beginTransaction();
	
	}
	/**
	 * �ύ����
	 */
	public function commit()
	{
		DriverAdapter::commit();
	
	}
	/**
	 * �ع�����
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
	 * �����ѯ(��������������)  array(array('left'=>'user'),array('user.id'=>'think_test'.id))
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
	 * ����having���൱�ڶ�group��������ٽ���where,����Ƚϸ��ӣ�������ʱ��ֻ֧���ַ�����ʽ
	 * @param string $having
	 * @return ModelBase
	 */
	public function having($having)
	{
		$this->strcut['having'] = $having;
		return $this;
	}
	/**
	 * limit array(1)|array(10,20) �淶��,��ʹ��һ������,Ҳ�����Խṹ���ķ�ʽ������,��֧���ַ���������ʽ
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
	 * ��ҳ-ע���һҳ��1,����0
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