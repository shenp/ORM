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
		$this->strcut['where'] = NULL;
		$this->strcut['action'] = NULL;
	
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
		if (empty($arguments) || !is_array($arguments))	return;
		$this->strcut['input'] = $arguments;
		return $this;
		
	}
    /**
     * ��ȡ��������
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
	 * ��ѯ���ݼ�-���ǵ�ҵ����Ҫ,���Բ�ѯ��Ϊ��ѯ���ݼ��Ͳ�ѯ������,��ѯ�����в���ȫ����������ʽ������,�ǵ���apiֻ��һ�����ݼ�С���ݿ�ѹ���ʹ���
	 * @return array
	 */
	public function getAll()
	{
		$this->strcut['action'] = self::ACTION_SELECT;
		$this->strcut['tableName'] = $this->getTableName();
		//TODO::֮���Կ��������ƴ��sql��䣬����Ϊֻ�������ݿ����������getAll getRow exec 3��ִ��sql���ķ����������������������߱������ṹ����������Ҳ��Ϊ�˿�����Աֱ�����Զ���sql��������̵�,����Ҳ����php�ٷ��ṩ��api�ͼ����ݿ�������д
		if (!isset($this->querySql)){
			$querySql = DriverAdapter::buildSql($this->strcut);
		}
		else{
			$querySql = $this->querySql;
		}
		$result  = DriverAdapter::getAll($querySql);
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
			$where[$fields['_pk']] = $pkValue;
			//����������where������ǰ�� �Ż�sql���
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
		//���sql�ṹ��
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