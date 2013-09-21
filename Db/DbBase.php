<?php
namespace HuiLib\Db;

/**
 * 数据库基础类
 * 
 * 包括适配器、工厂功能，因为包含太多文件实在影响性能和维护，尽量保持简洁构架
 *
 * @author 祝景法
 * @since 2013/09/03
 */
abstract class DbBase
{
	/**
	 * 数据库连接
	 * 
	 * @var \PDO
	 */
	protected $connection;
	
	/**
	 * 数据库驱动 如mysql
	 */
	protected $driver;
	
	/**
	 * 数据库主从配置
	 */
	private static $config;

	/**
	 * 获取数据库连接，便于直接查询
	 */
	public function getConnection()
	{
		return $this->connection;
	}
	
	/**
	 * 获取具体配置驱动实例
	 */
	public function getDriver()
	{
		return $this->driver;
	}
	
	/**
	 * 设置数据库配置
	 * @param array $config
	 */
	public static function setConfig($config){
		self::$config=$config;
	}
	
	/**
	 * 创建DB Master实例
	 */
	public static function createMaster()
	{
		if (empty(self::$config['master'])) {
			throw new \HuiLib\Error\Exception('Db master config can not be empty!');
		}

		return self::doingCreate(self::$config['master']);
	}
	
	/**
	 * 创建DB Slave实例
	 */
	public static function createSlave($slaveNode=NULL)
	{
		if (empty(self::$config['slave'])) {
			throw new \HuiLib\Error\Exception('Empty slave config!');
		}
		
		$slaveConfig=self::$config['slave'];
		if (empty($slaveNode)) {
			$dbConfig=$slaveConfig[array_rand($slaveConfig)];
		}elseif (isset(self::$slaveConfig[$slaveNode])){
			$dbConfig=self::$slaveConfig[$slaveNode];
		}else{
			throw new \HuiLib\Error\Exception('Specified slave config is empty!');
		}
		
		return self::doingCreate($dbConfig);
	}
	
	/**
	 * 创建DB实例 DB factory方法
	 */
	private static function doingCreate($dbConfig){
		if (empty($dbConfig['adapter'])) {
			throw new \HuiLib\Error\Exception('Db adapter can not be empty!');
		}

		switch ($dbConfig['adapter']){
			case 'pdo':
				$adapter=new \HuiLib\Db\Pdo\PdoBase($dbConfig);
				break;
			case 'mongo':
		
				break;
		}
		
		return $adapter;
	} 
	
	/**
	 * 开启一个事务
	 */
	abstract public function beginTransaction();
	
	/**
	 * 开启一个事务
	 */
	abstract public function commit();
	
	/**
	 * 事务回滚
	 */
	abstract public function rollback();
}