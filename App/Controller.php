<?php
namespace HuiLib\App;

use HuiLib\App\Front;
use HuiLib\Request\RequestBase;

/**
 * 控制器基础类
 * 
 * @author 祝景法
 * @since 2013/09/20
 */
class Controller
{
	/**
	 * 请求对象
	 * @var \HuiLib\View\ViewBase
	 */
	protected $view = NULL;
	
	/**
	 * 是否使用View输出
	 * @var boolean
	 */
	protected $useView = TRUE;
	
	/**
	 * 是否自动渲染并输出
	 * @var boolean
	 */
	protected $autoRender = TRUE;
	
	/**
	 * 是否Ajax请求
	 * 
	 * @var boolean
	 */
	protected $ajax = FALSE;
	
	//Ajax状态返回
	const STATUS_SUCCESS=TRUE;
	const STATUS_FAIL=FALSE;

	public function __construct()
	{
		//是否Ajax请求
		$this->ajax=\HuiLib\Helper\Param::isXmlHttpRequest();
		
		//控制器子类初始化接口
		$this->init();
	}
	
	/**
	 * init作为控制器初始化接口，在dispatch前调用
	 */
	protected function init()
	{
	}

	/**
	 * 任务分发
	 */
	public function dispatch()
	{
	    // Dispatch前执行
		$this->onBeforeDispatch ();
		
		if ($this->useView) {
			$this->initView ();
		}
		
		//路由方法 附加操作后缀
		$request=Front::getInstance()->getRequest();
		$this->action=$request->getActionRouteSeg();
		$action=$request->mapRouteSegToMethod($this->action).'Action';

		if (method_exists($this, $action)) {
		    //路由方法：通过获取对象方法，并判断调用方法是否存在来判断参数是否精确匹配
		    if (strtolower($this->action) != $this->action 
		          || !in_array($action, get_class_methods($this))) {
		        exit("Bad url route action format.");
		    }
		    $this->$action();
		}else{
		    //App namespace route
		    $appNameLoader=new \HuiLib\Route\AppName(RequestBase::SEG_ACTION);
		    Front::getInstance()->setAppNameRoute($appNameLoader);
		    
		    //二级目录路由处理
		    $appNameLoader->route();
		    
		    //var_dump($request->getRouteInfo());die();
		    //TODO  reload action
		}
		
		$this->onAfterDispatch ();
		
		//渲染模板，自动触发或方法中调用
		if ($this->useView && $this->autoRender) {
			$this->renderView ();
		}
	}


	/**
	 * 请求派发前事件
	 */
	protected function onBeforeDispatch()
	{
	}

	/**
	 * 请求派发后事件
	 */
	protected function onAfterDispatch()
	{
	}

	/**
	 * 渲染
	 * 
	 * 在autoRender关闭的情况下
	 */
	protected function renderView($view = NULL, $ajaxDelimiter = NULL)
	{
		//关闭自动渲染情况下可能未初始化
		$this->initView();
		
		$this->preRenderView();
		
		if ($view === NULL) {
		    $request=Front::getInstance()->getRequest();
			$view = ucfirst ( $request->getPackageRouteSeg() ) . SEP . ucfirst ( $request->getControllerRouteSeg() ) . SEP . ucfirst ( $request->getActionRouteSeg() );
		}
		
		$this->view->render ( $view, $ajaxDelimiter );
		
		$this->postRenderView();
		
		//renderView渲染输出后结束
		exit();
	}
	
	/**
	 * 渲染前事件
	 */
	protected function preRenderView()
	{
		//有View类型的才像前台赋值配置数据
		$this->getSiteConfig();
		$this->view->assign(Front::getInstance()->getSiteConfig()->getByKey());
	}
	
	/**
	 * 渲染后事件
	 */
	protected function postRenderView()
	{
	}
	
	/**
	 * 输出JSON数据
	 * 
	 * JSON数据结构:{"data":{},"success":true,"message":"","extra":{code:200, forward:'', float:true}}
	 *     success:请求状态
	 *     message:前台客户端用户友好的提示信息
	 *     extra:请求相关的额外状态数据，例如返回代码，ajax链接跳转指令（是否在浮动窗口中，例如登录）等
	 *     data:要传输给客户端的业务数据
	 * 
	 * @param boolean $status
	 * @param string $message 返回代码
	 * @param int $extra 请求相关的额外状态数据
	 * @param mix $data 返回数据
	 */
	protected function renderJson($status=self::STATUS_SUCCESS, $message='', $extra=array(), $data=array())
	{
		$result=array();
		
		$result['success']=$status;
		$result['message']=$message;
		if (!isset($extra['code'])) {
		    $extra['code']=\HuiLib\Helper\Header::OK;
		}
		$result['extra']=$extra;
		$result['data']=$data;
		$json=json_encode ( $result );
		
		$callback=\HuiLib\Helper\Param::get('callback', \HuiLib\Helper\Param::TYPE_STRING);
		if ($callback) {
			$json=$callback."($json)";
		}
		
		echo $json;
		die();
	}
	
	/**
	 * 直接将结果集作为数组输出
	 * 
	 * @param array $result
	 */
	protected function renderJsonResult($result)
	{
		 $extra=array();
		 $status=self::STATUS_SUCCESS;
		 $message='';
		 $data=array();
		 
		 if (isset($result['extra'])) {
		 	$extra=$result['extra'];
		 	unset($result['extra']);
		 }
		 if (isset($result['success'])) {
		 	$status=$result['success'];
		 	unset($result['success']);
		 }
		 if (isset($result['message'])) {
		 	$message=$result['message'];
		 	unset($result['message']);
		 }
		 if (isset($result['data'])) {
		 	$data=$result['data'];
		 	unset($result['data']);
		 }else{
		 	$data=$result;
		 }
		 
		 $this->renderJson($status, $message, $extra, $data);
	}

	/**
	 * 输出
	 */
	public function output()
	{
	}

	/**
	 * 向前端赋值一个变量
	 * 
	 * 仅是桥接方法到: \HuiLib\View\ViewBase::assign($key, $value) 
	 */
	protected function assign($key, $value = NULL)
	{
		$this->view->assign ( $key, $value );
		
		return $this;
	}

	/**
	 * 初始化视图对象
	 */
	protected function initView()
	{
		if ($this->view===NULL) {
			$this->view = new \HuiLib\App\View ();
			Front::getInstance()->setView($this->view);
		}
	}
	
	/**
	 * 初始化应用配置实例
	 */
	protected function getAppConfig()
	{
	    return Front::getInstance()->getAppConfig();
	}
	
	/**
	 * 初始化网站配置实例
	 */
	protected function getSiteConfig()
	{
		return Front::getInstance()->getSiteConfig();
	}

	/**
	 * 取消使用view
	 */
	protected function disableView()
	{
		$this->useView = FALSE;
	}

	/**
	 * 开启view功能
	 */
	protected function enableView()
	{
		$this->useView = TRUE;
	}

	/**
	 * 取消自动触发模板渲染
	 */
	protected function disableAutoRender()
	{
		$this->autoRender = FALSE;
	}

	/**
	 * 获取翻译实例
	 */
	protected function getLang()
	{
		return Front::getInstance()->getLang();
	}
}
