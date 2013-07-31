<?php
class LinqExecute
{
	private $temp;
	private $db_master_link;
	private $db_slave_link;
	
	private function getTempResult()
	{
		$tempKey = md5($sql);
		if (isset(self::$temp[$tempKey]) && !empty(self::$model[$tempKey]))
			return self::$temp[$tempKey];
		
	}
	private function getMasterLink()
	{
		if (isset($this->db_master_link) && !empty($this->$db_master_link))
			return $this->db_master_link;
		$this->db_master_link = new PDO($this->dbConf['db_dns'], base64_decode($this->dbConf['db_username']), base64_decode($this->dbConf['db_passwd']));
		return $this->db_master_link;
	}
	private function getSlaveLink()
	{
		if (isset($this->db_slave_link) && !empty($this->db_slave_link))
			return $this->db_slave_link;
		$this->db_slave_link = new PDO($this->dbConf['db_dns'], base64_decode($this->dbConf['db_username']), base64_decode($this->dbConf['db_passwd']));		
		return $this->db_slave_link;
	}
	
	public function fetch()
	{
		$sql = $this->getSql();
		$temp = $this->getTempResult($sql,$params);
		if (!empty($temp))
			return $temp;
		$db = $this->getSlaveLink();
		$db->prepare($sql);
		$db->execute($this->fields);
		return $db->fetch($this->fetchStyle);
	}
	public function exec()
	{
		$sql = $this->getSql();
		$temp = $this->getTempResult($sql,$params);
		if (!empty($temp))
			return $temp;
		$db = $this->getMasterLink();
		$db->prepare($sql);
		$db->execute($this->fields);
		
	}
	public function fetchAll($sql,$fields,$style)
	{
		$sql = $this->getSql();
		$temp = $this->getTempResult($sql,$params);
		if (!empty($temp))
			return $temp;
		$db = $this->getMasterLink();
		$db->prepare($sql);
		$db->execute($this->fields);
		return $db->fetchAll($this->fetchStyle);
	}
	
	
}