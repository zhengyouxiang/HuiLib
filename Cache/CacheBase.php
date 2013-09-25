<?php
namespace HuiLib\Cache;

/**
 * 缓存功能基础类
 *
 * @author 祝景法
 * @since 2013/09/15
 */
abstract class CacheBase
{
	/**
	 * 缓存内部连接
	 *
	 * @var CacheBase
	 */
	protected $connect;
	
	/**
	 * 缓存初始化配置
	 * @var array
	 */
	protected $config;
	
	protected function __construct()
	{
	}
	
	/**
	 * 保存一个缓存
	 * 
	 * @param string $key 缓存键
	 * @param mix $value 缓存值
	 */
	public abstract function add($key, $value);
	
	/**
	 * 保存一个缓存
	 * 
	 * 默认调用add版本，类似memcache有独立实现的，可以覆盖
	 */
	public function replace($key, $value){
		return $this->add($key, $value);
	}
	
	/**
	 * 删除一个缓存
	 * 
	 * @param string $key 缓存键
	 */
	public abstract function delete($key);
	
	/**
	 * 获取一个缓存内容
	 * 
	 * @param string $key 缓存键
	 */
	public abstract function get($key);
	
	/**
	 * 清空所有数据
	 *
	 */
	public function flush(){
		
	}
	
	/**
	 * 给缓存值加上一个数
	 *
	 * @param string $key 缓存键
	 * @param mix $value 增加的值
	 */
	public function increase($key, $value=1){
	
	}
	
	/**
	 * 给缓存值减去一个数
	 *
	 * @param string $key 缓存键
	 * @param mix $value 减少的值
	 */
	public function decrease($key, $value=1){
	
	}
	
	/**
	 * 批量保存一个缓存
	 * 
	 * @param array $assocArray 关联数组
	 */
	public function saveBatch($assocArray)
	{
		foreach ($assocArray as $key=>$value){
			$this->add($key, $value);
		}
	}
	
	/**
	 * 批量删除一个缓存
	 * 
	 * @param array $keyArray 缓存键数组
	 */
	public function deleteBatch($keyArray)
	{
		foreach ($keyArray as $key){
			$this->delete($key);
		}
	}
	
	/**
	 * 批量获取一个缓存内容
	 * 
	 * @param array $keyArray 缓存键数组
	 */
	public function getBatch($keyArray)
	{
		$result=array();
		foreach ($keyArray as $key){
			$result[$key]=$this->get($key);
		}
		
		return $result;
	}

	/**
	 * 创建Cache实例factory方法
	 */
	public static function create($config)
	{
		if (empty ( $config ['adapter'] )) {
			throw new \HuiLib\Error\Exception ( 'Cache adapter can not be empty!' );
		}
		
		switch ($config ['adapter']) {
			case 'redis' :
				$adapter = new \HuiLib\Cache\Storage\Redis ( $config );
				break;
			case 'memcache' :
				$adapter = new \HuiLib\Cache\Storage\Memcache ( $config );
				break;
			case 'apc' :
				$adapter = new \HuiLib\Cache\Storage\Apc ( $config );
				break;
			case 'file' :
				$adapter = new \HuiLib\Cache\Storage\File ( $config );
				break;
		}
		
		return $adapter;
	}
	
	/**
	 * 将方法重新定位到Redis对象
	 */
	function __call($method, $arguments){
		if (method_exists($this->connect, $method)){
			//需要返回结果
			return call_user_func_array(array($this->connect, $method), $arguments);
		}else{
			throw new \HuiLib\Error\Exception ( "出错了，提交了一个该缓存服务端暂不支持的方法:{$method}" );
		}
	}
}