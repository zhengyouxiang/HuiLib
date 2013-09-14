<?php
namespace HuiLib\Db\Query;

/**
 * Sql语句查询类Delete操作
 *
 * @author 祝景法
 * @since 2013/09/03
 */
class Delete extends \HuiLib\Db\Query
{
	const DELETE = 'delete';
	const TABLE = 'table';
	const WHERE = 'where';
	const LIMIT = 'limit';
	
	/**
	 * 重置语句部分参数
	 *
	 * @param array $part
	 * @return \HuiLib\Db\Query\Select
	 */
	public function reset($part)
	{
		switch ($part) {
			case self::TABLE :
				$this->table = NULL;
				break;
			case self::WHERE :
				$this->where = array();
				break;
			case self::LIMIT :
				$this->limit = NULL;
				break;
			case self::ENDS :
				$this->ends = '';
				break;
		}
		return $this;
	}

	/**
	 * 编译成SQL语句
	 */
	protected function compile(){
		$parts=array();
		$parts['start']='delete from';
		$parts[self::TABLE]=$this->renderTable();
		$parts[self::WHERE]=$this->renderWhere();
		$parts[self::LIMIT]=$this->renderLimit();
		$parts[self::ENDS]=$this->ends;
		
		$this->parts=&$parts;
		return parent::compile();
	}
	
	/**
	 * 生成SQL语句
	 */
	public function toString(){
		return $this->compile();
	}
	
	public function table($table){
		parent::table($table);
	
		return $this;
	}
	
	public function where($where, $operator=self::WHERE_AND){
		parent::where($where, $operator);
	
		return $this;
	}
	
	public function limit($limit){
		parent::limit($limit);
	
		return $this;
	}
	
}