<?php
namespace HuiLib\Db\Test;

/**
 * 数据库Query测试类
 *
 * @author 祝景法
 * @since 2013/09/13
 */
class QueryDeleteTest extends \HuiLib\Test\TestBase
{
	public function run(){
		$this->test();
	}
	
	/**
	 * 测试
	 */
	private function test(){
		$delete=\HuiLib\Db\Query::delete()->table('test')->where(array('id=1379182395'))->limit(10);
		echo $delete->query();
		echo $delete->toString();
	}

	protected static function className(){
		return __CLASS__;
	}
}