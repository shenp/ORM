<?php
class think_test extends ModelBase
{
	protected $connection = 'db';
	protected  $tableName = 'Ip';
	
	function  getTop2()
	{
		$data[] = $this->getRow(1);
		$data[] =$this->getRow(2);
		
		return $data;
	
	}
	


}