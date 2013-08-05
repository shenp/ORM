<?php
/**
 * sql�ν����� - ����sql�ṹ�����ɸ�sql��
 * ֮����Ҫ�Ѹ���sql�ṹ�����������ֵķ������������,����Ϊ,��������ݿ�������̫���,��Ȼ˵����sql��Ӧ���������౾ְ����,���Ҹ������ݿ����ӷ�ʽ�Ĳ�ͬ,sql����в��졣
 * ����������첻�����Ե������һ������,���ݿ������̳и���,����sql����,�����ݿ��������п�����д�䷽��
 * @author sp
 */
abstract class ParseSql
{
	// �ֶι�ϵ�����
    protected $relation_operation = array('eq'=>'=','neq'=>'<>','gt'=>'>','egt'=>'>=','lt'=>'<','elt'=>'<=','notlike'=>'NOT LIKE','like'=>'LIKE','in'=>'IN','notin'=>'NOT IN');
	
    protected $querySql_input = array();	//pdo��ѯ���������ֶ�����ֵ��map��ϵ
    
    
    /**
     * �����ֶ�������prepare���滻����
     * @param string $field
     * @param string|array $value
     * @return string
     */
    protected function __parseInput($field,$value)
	{
		$replaceInput = '';
		if (empty($field) || !isset($value)) return $replaceInput;
		//prepare ���滻�������ܳ��֡������ţ���.�� ���������滻��
		$field = str_replace(array('`','.'),array('','_'), $field);
		if (is_array($value)){
			foreach ($value as $index=>$v){
				$this->querySql_input[':'.$field.$index] = $v;
				$replaceInput[] = ':'.$field.$index;
			}
		}else{
			$this->querySql_input[':'.$field] = $value;
			$replaceInput = ':'.$field;
		}
		return $replaceInput;
	}
	/**
	 * ��֤�ֶι�ϵ���ӷ�logic
	 * @param array $where
	 * @return array
	 */
	protected function __validateLogic(&$where)
	{
		$operate = array();
		if (!isset($where['_logic']))	return $operate;
		foreach ($where['_logic'] as $logic){
			$logic = strtoupper($logic);
			if(in_array($logic,array('AND','OR','XOR'))){
                $operate[]    =   ' '.$logic.' ';
            }else{
                $operate[]    =   ' AND ';
            }
		}
		unset($where['_logic']);
		return $operate;
	}
	/**
	 * field �ӡ���,��ֹ�ֶ���Ϊmysql�ؼ��֣������µ�sql����,����db.table��ʽ
	 * @param string $field �ֶ���
	 * @return string
	 */
	private function addFieldMark(&$field)
	{
		if (strpos($field, '.') !== FALSE){
			$map = explode('.', $field);
			foreach ($map as &$v){
				$v = '`'.trim($v).'`';
			}
			$field = implode('.', $map);
		}else{
			$field = '`'.trim($field).'`';
		}
		return $field;
	}
	/**
	 * ����ƴװwhere�����Ӿ�
	 * @param array $where
	 * @return String
	 */
	protected function __parseSqlWhere($where)
	{
		$whereStr = '';
		$fieldsNum = 0;
		if (empty($where))	return $whereStr;
		
		$operate = $this->__validateLogic($where);
		foreach ($where as $field=>$item){
			$this->addFieldMark($field);
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
	                        $whereStr .= $field.' '.$data.' '.$rule.' ';
	                    }else{
	                        $op = is_array($item[$i])?$this->relation_operation[strtolower($item[$i][0])]:'=';
	                        $whereStr .= $field.' '.$op.' '.$this->__parseInput($field.$i,$data).' '.$rule.' ';
	                    }
	                }
	                $whereStr = substr($whereStr,0,-4);
	            }
				
				
			}else{
				$whereStr.= $field." = ".$this->__parseInput($field,$item)."";
			}
			$logic = isset($operate[$fieldsNum])?$operate[$fieldsNum]:' AND ';
			$whereStr .= $logic;
			$fieldsNum++;
		}
		$whereStr = " WHERE ".substr($whereStr,0,-strlen($logic));
		
		return $whereStr;
	}
	/**
	 * ����order
	 * @param array $order
	 * @return string
	 */
	protected function __parseSqlOrder($order)
	{
		$orderStr = '';
		if (empty($order)) return $orderStr ;
		if (is_array($order)){
			foreach ($order as $filed=>$keyword){
				if(is_numeric($filed)){
					$kw = 'ASC';
					$filed = $keyword;
				}else{
					$kw = strtoupper($keyword);
				}
				$this->addFieldMark($filed);
				$orderStr .= $filed.' '.$kw.',';
			}
			$orderStr = substr($orderStr,0,-1);
		}
		//ϣ��ͳһ�淶���������ַ�����ʽ,��������д����΢����һ��ƪ��
//		else{
//			$orderStr = trim($order);
//		}
		return ' ORDER BY '.$orderStr;
	}
	/**
	 * ����fields
	 * @param array $fileds
	 * @return string
	 */
	protected function __parseSqlFields($fields)
	{
		$fieldStr = '';
		if (empty($fields)) return $fieldStr ;
		if (is_array($fields)){
			foreach ($fields as $k=>$alias){
				if(is_numeric($k)){
					if (strpos($alias, '(') !== FALSE){
						$filed = str_replace(array('(',')'), array('(`','`)'), $alias);
					}elseif(strpos($alias, '.') !== FALSE){ //array('user.id')
						$map = explode('.', $alias);
						foreach ($map as &$value){
							$value = '`'.trim($value).'`';
						}
						$filed = implode('.', $map);
					}
					else{
						$filed = '`'.$alias.'`';
					}
				}else{
					if (strpos($k, '(') !== FALSE){
						$filed = str_replace(array('(',')'), array('(`','`)'), $k).' AS `'.$alias.'`';
					}elseif(strpos($k, '.') !== FALSE){ //array('user.id'=>'u_id')
						$map = explode('.', $k);
						foreach ($map as &$value){
							$value = '`'.trim($value).'`';
						}
						$filed = implode('.', $map).' AS `'.$alias.'`';
					}else{
						$filed = '`'.$k.'` AS `'.$alias.'`';
					}
				}
				$fieldStr .= $filed.',';
			}
			$fieldStr = substr($fieldStr,0,-1);
		}
//		else{	
//			$fieldsArr = explode(',', $fields);
//			foreach ($fieldsArr as &$fields){
//				$fields = '`'.trim($fields).'`';
//			}
//			$fieldStr = implode(',', $fieldsArr);
//		}
		return $fieldStr;
	}
	/**
	 * ����group
	 * @param array $group
	 * @return string
	 */	
	protected function __parseSqlGroup($group)
	{
		$groupStr = '';
		if (empty($group)) return $groupStr ;
		if (is_array($group)){
			foreach ($group as $filed){
				$this->addFieldMark($filed);
				$groupStr .= $filed.',';
			}
			$groupStr = substr($groupStr,0,-1);
		}
		return ' GROUP BY '.$groupStr;
	}
	/**
	 * ����having-����having���൱�ڶ�group��������ٽ���where,����Ƚϸ��ӣ�������ʱ��ֻ֧���ַ�����ʽ
	 * @param array $having
	 * @return string
	 */	
	protected function __parseSqlHaving($having)
	{
		$havingStr = '';
		if (empty($having)) return $havingStr ;
		return ' HAVING '.$having;
	
	}
	/**
	 * ����join ���ﲻ֮���Բ��ṩ���������ʽ������Ϊ��������ܴ�̶��������������sql���̫����sql���ƪ��,��������������ģʽ����󽵵��˿�����Ա��дsql�ɱ�,sql�Զ����ɣ������ò��ű����
	 * array(array('left'=>'user'),array('user.id'=>'think_test'.id))
	 * @param array $join
	 * @return string
	 */
	protected function __parseSqlJoin($join)
	{
		$joinStr = '';
		if (empty($join)) return $joinStr ;
		$tableStr = $relationStr = '';
		$i = 0;
		$joinMark = $join[0];
		$relationFields = $join[1];
		if (!is_array($joinMark) || !is_array($relationFields) || count($join) != 2) return $joinStr;
		
		$relationFieldsKeys = array_keys($relationFields);
		foreach ($joinMark as $mark=>$table){
			$mark = strtoupper($mark);
			$mark = in_array($mark, array('INNER','LEFT','RIGHT')) === FALSE?'INNER':$mark;
			$bRelationField = $relationFieldsKeys[$i];
			$aRelationField = $relationFields[$relationFieldsKeys[$i]];
			$relationStr .= $mark.' JOIN '.$this->addFieldMark($table).' ON '.$this->addFieldMark($bRelationField).' = '.$this->addFieldMark($aRelationField).' ';
			$i++;
		}
		$joinStr = ' '.$tableStr.substr($relationStr, 0,-1);
		return $joinStr;
	}
	/**
	 * ����limit
	 * @param array $limit array(2)|array(2,10)
	 * @see http://starlight36.com/post/php-pdo-limit-bug
	 * @return string
	 */
	protected function __parseSqlLimit($limit)
	{
		$limitStr = '';
		if (empty($limit)) return $limitStr ;
		
		$limit_keys = array_keys($limit);
		$limitStr = ' LIMIT '.$this->__parseInput($limit_keys[0], $limit[$limit_keys[0]]);
		if (isset($limit_keys[1]))
			$limitStr.=','.$this->__parseInput($limit_keys[1], $limit[$limit_keys[1]]);
		return $limitStr;
	}
	/**
	 * ����insert������Դ
	 * @param array $data һλ����
	 * @return string
	 */
	protected function __parseSqlInsertData($data)
	{
		$insertStr = $values = $fields= '';
		if (empty($data)) return $insertStr ;

		foreach ($data as $field=>$value){
			$fields .= $this->addFieldMark($field).',';
			$values .= $this->__parseInput($field, $value).',';
		}
		$insertStr = '('.substr($fields,0,-1).') VALUES ('.substr($values, 0,-1).')';
		return $insertStr;
	}
	/**
	 * ����update������Դ
	 * @param array $data
	 * @return string
	 */
	protected function __parseSqlUpdateData($data)
	{
		$updateStr = $values = $fields= '';
		if (empty($data)) return $updateStr ;
		
		foreach ($data as $field=>$value){
			$updateStr .= $this->addFieldMark($field).' = '.$this->__parseInput($field, $value).',';
		}
		$updateStr = 'SET '.substr($updateStr,0,-1);
		return $updateStr;
	
	}		

}