<?php
/**
 * 抽象数据库驱动类-定义任何数据库驱动(mysql/pdo/mysqli/mssql)应该具备的规范
 * @author sp
 */
interface Adrive
{
	//构建sql语句
	public function buildSql($struct);
	
	//查询数据集
	public function getAll($querySql);
	
	//查询数据行
	public function getRow($querySql);
	
	//执行无结果集的sql语句
	public function exec($querySql);
	
	
}