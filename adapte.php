<?php
/**
 * 产生具体数据库的工厂类
 * @author shenpeng
 */
class Adapte
{
	private static $model;
	
	private static function getModel()
	{
		if (!isset(self::$model) || empty(self::$model))
			return	self::$model = new Mysql();
		
	}
	
	static function getSql($oTree)
	{
		$model = self::getModel();
		return $model->generateSql($oTree);
	}
	
	
}