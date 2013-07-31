<?php
/**
 * ���ݿ������� --��������tree��ƴװsql ����ִ��sql���
 * @author sp
 *
 */
class PDODrive	implements Adrive
{
	private $db_master_link;	//�����ݿ�����
	
	private $db_slave_link;		//�����ݿ�����
	
	private $connection;	//���ݿ����Ӵ�
	
	private $is_ms_link	= FALSE;	//�Ƿ�Ϊ���Ӽܹ�,Ŀǰ��֧�����Ӽܹ�
	
	private $querySql_input = array();	//pdo��ѯ���������ֶ�����ֵ��map��ϵ
	
	private $sql_select_tpl = 'SELECT%DISTINCT% %FIELD% FROM %TABLE%%JOIN%%WHERE%%GROUP%%HAVING%%ORDER%%LIMIT%%UNION%%COMMENT%';
	
	// �ֶι�ϵ�����
    protected $relation_operation = array('eq'=>'=','neq'=>'<>','gt'=>'>','egt'=>'>=','lt'=>'<','elt'=>'<=','notlike'=>'NOT LIKE','like'=>'LIKE','in'=>'IN','notin'=>'NOT IN');
    
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
	
	private function __parseInput($field,$value)
	{
		$replaceInput = '';
		if (empty($field) || empty($value)) return;
		if (is_array($value)){
			foreach ($value as $index=>$v){
				$this->querySql_input[$field.':'.$index] = $v;
				$replaceInput[] = $field.':'.$index;
			}
		}else{
			$this->querySql_input[':'.$field] = $value;
			$replaceInput = ':'.$field;
		}
		return $replaceInput;
	}
	/**
	 * ����ƴװwhere�����Ӿ�
	 * @param array $where
	 * @return String
	 */
	private function __parseSqlWhere($where)
	{
		$whereStr = '';
		//TODO empty
		if (isset($where)){
			$operate  = isset($where['_logic'])?strtoupper($where['_logic']):'';
            if(in_array($operate,array('AND','OR','XOR'))){
                $operate    =   ' '.$operate.' ';
                unset($where['_logic']);
            }else{
                $operate    =   ' AND ';
            }
			foreach ($where as $field=>$item){
				if (is_array($item)){
		            if(is_string($item[0])) {
		                if(preg_match('/^(EQ|NEQ|GT|EGT|LT|ELT)$/i',$item[0])) { // �Ƚ�����
		                    $whereStr .= $field.' '.$this->relation_operation[strtolower($item[0])].' '.$this->__parseInput($field,$item[1]);
		                }elseif(preg_match('/^(NOTLIKE|LIKE)$/i',$item[0])){// ģ������
		                    if(is_array($item[1])) {
		                        $likeLogic  =   isset($item[2])?strtoupper($item[2]):'AND';
		                        if(in_array($likeLogic,array('AND','OR','XOR'))){
		                            $likeStr    =   $this->relation_operation[strtolower($item[0])];
		                            $like       =   array();
		                            foreach ($item[1] as $value){
		                                $like[] = $field.' '.$likeStr.' '.$this->__parseInput(str_replace('%', '', $value),$value);
		                            }
		                            $whereStr .= implode(' '.$likeLogic.' ',$like);                          
		                        }
		                    }else{
		                        $whereStr .= $field.' '.$this->relation_operation[strtolower($item[0])].' '.$this->__parseInput($field,$item[1]);
		                    }
		                }elseif('exp'==strtolower($item[0])){ // ʹ�����ֱ��ʽ,û�ж�������ת��,���ܵ���sqlע��,��Ҫע��
		                    $whereStr .= ' '.$field.' '.$item[1].' ';
		                }elseif(preg_match('/IN|NOTIN/i',$item[0])){ // IN ����
	                        if(is_string($item[1])) {
	                             $item[1] =  explode(',',$item[1]);
	                        }
	                        $zone      =   implode(',',$this->__parseInput($field,$item[1]));
	                        $whereStr .= $field.' '.strtoupper($item[0]).' ('.$zone.')';
		                }elseif(preg_match('/BETWEEN/i',$item[0])){ // BETWEEN����
		                    $data = is_string($item[1])? explode(',',$item[1]):$item[1];
		                    $whereStr .=  ' '.$field.' '.strtoupper($item[0]).' '.$this->__parseInput($field,$data[0]).' AND '.$this->__parseInput($field,$data[1]).' ';
		                }else{
		                    //�쳣
		                }
		            }else {
		                $count = count($item);
		                $rule  = isset($item[$count-1]) && !is_array($item[$count-1])?strtoupper($item[$count-1]):'';
		                if(in_array($rule,array('AND','OR','XOR')) ) {
		                    $count  = $count -1;
		                }else{
		                    $rule   = 'AND';
		                }
		                for($i=0;$i<$count;$i++) {
		                    $data = is_array($item[$i])?$item[$i][1]:$item[$i];
		                    if('exp'==strtolower($item[$i][0])) {
		                        $whereStr .= '`'.$field.'` '.$data.' '.$rule.' ';
		                    }else{
		                        $op = is_array($item[$i])?$this->relation_operation[strtolower($item[$i][0])]:'=';
		                        $whereStr .= '`'.$field.'` '.$op.' '.$this->__parseInput($field.$i,$data).' '.$rule.' ';
		                    }
		                }
		                $whereStr = substr($whereStr,0,-4);
		            }
					
					
				}else{
					$whereStr.= '`'.$field.'`'." = ".$this->__parseInput($field,$item)."";
				}
				$whereStr .= $operate;
			}
			$whereStr = " WHERE ".substr($whereStr,0,-strlen($operate));;
		}
		return $whereStr;
	}
	/**
	 * ����sql�ṹ������sql���
	 * @param Object $struct �ṹ��
	 * @return String $sql sql�ַ���
	 */
	public function buildSql($struct)
	{
		$querySql = array();
		$querySql['input'] = isset($struct['input'])?$struct['input']:array();
		if ($struct['action'] == ModelBase::ACTION_SELECT)
		{
			$querySql['sql'] = str_replace(
	            array('%TABLE%','%DISTINCT%','%FIELD%','%JOIN%','%WHERE%','%GROUP%','%HAVING%','%ORDER%','%LIMIT%','%UNION%','%COMMENT%'),
	            array(
	                isset($struct['tableName'])?$struct['tableName']:'',
	                isset($struct['distinct'])?$struct['distinct']:'',
	                isset($struct['field'])?$struct['field']:'*',
	                isset($struct['join'])?$struct['join']:'',
	                $this->__parseSqlWhere(isset($struct['where'])?$struct['where']:NULL),
	                isset($struct['group'])?$struct['group']:'',
	                isset($struct['having'])?$struct['having']:'',
	                isset($struct['order'])?$struct['order']:'',
	                isset($struct['limit'])?$struct['limit']:'',
	                isset($struct['union'])?$struct['union']:'',
	                isset($struct['comment'])?$struct['comment']:''
	                ),
	             $this->sql_select_tpl);
			
		}elseif ($struct['action'] == ModelBase::ACTION_INSER){
		
		
		}elseif ($struct['action'] == ModelBase::ACTION_DELETE)
		{
		
		}elseif ($struct['action'] == ModelBase::ACTION_UPDATE)
		{
		
		
		}elseif ($struct['action'] == ModelBase::ACTION_SHOW_COLUMS)
		{
			$querySql['sql'] = str_replace('%TABLE%', isset($struct['tableName'])?$struct['tableName']:'', $this->sql_show_colums_tpl);
		}
		//�ϲ�modelbase(modelbase��inputһ����Դ�ڿ������Լ�����)��input�ͽ��������input
		$querySql['input'] = array_merge($querySql['input'],$this->querySql_input);
		return $querySql;
	}
	private function query($querySql)
	{
		$this->initConnect();
		$dbLink = $this->is_ms_link?$this->db_slave_link:$this->db_master_link;
		$sth = $dbLink->prepare($querySql['sql']);
		$sth->execute($querySql['input']);
		return $sth;
	
	}
	public function exec($querySql)
	{
		$this->initConnect();
		$dbLink = $this->is_ms_link?$this->db_slave_link:$this->db_master_link;
		return $dbLink->exec($querySql['sql']);
	
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
	public function getAll($querySql)
	{
		$sth = $this->query($querySql);
		return $sth->fetchAll();
	}
	
	public function getRow($querySql)
	{
		$sth = $this->query($querySql);
		return $sth->fetch();;
	}



}