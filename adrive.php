<?php
/**
 * �������ݿ�������-�����κ����ݿ�����(mysql/pdo/mysqli/mssql)Ӧ�þ߱��Ĺ淶
 * @author sp
 */
interface Adrive
{
	//����sql���
	public function buildSql($struct);
	
	//��ѯ���ݼ�
	public function getAll($querySql);
	
	//��ѯ������
	public function getRow($querySql);
	
	//ִ���޽������sql���
	public function exec($querySql);
	
	
}