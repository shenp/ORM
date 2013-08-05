<?php
class ORMException extends Exception
{
	public function __construct($message)
	{
		parent::__construct($message);
	
	}
	public function errorMessage()
	{
		$error = $this->getMessage();
		return $error;
	
	}

}