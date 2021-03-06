<?php
namespace HuiLib\Module\Cdn\Server;

use HuiLib\Error\Exception;
use HuiLib\App\Front;
use HuiLib\Helper\Param;

/**
 * HuiLib CDN基础类库
 * 
 * @author 祝景法
 * @since 2014/03/25
 */
class Base extends  \HuiLib\Module\ModuleBase
{
    protected $appList=NULL;
    
    /**
     * 获取cdn配置
     */
    protected function getConfig()
    {
        $config=Front::getInstance()->getAppConfig()->getByKey('cdn');
        if (!isset($config['save_path']) || !isset($config['hash_length']) || !isset($config['directory_depth']) || !isset($config['app_list'])) {
            throw new \Exception('App.ini cdn config section is required.');
        }
        
        return $config;
    }
    
    public function getAppList()
    {
        if ($this->appList===NULL) {
            $config=$this->getConfig();
            $this->appList = new \HuiLib\Config\ConfigBase ( $config['app_list'] );
        }
    
        return $this->appList;
    }
    
    /**
     * 获取AppId的密钥
     */
    public function getAppSecret($appId)
    {
        if (empty($appId)) {
            throw new Exception('AppId can not be null.');
        }

        $app=$this->getAppList()->getByKey('app.'. $appId);

        if (empty($app['secret'])) {
            throw new Exception('AppList.ini set secret is required.');
        }
        
        return $app['secret'];
    }
    
    /**
     * 生成一个新文件的储存路径
     */
    protected function getNewFilePath($meta)
    {
        if (empty($meta['size']) || empty($meta['type']) || empty($meta['name'])) {
            throw new Exception(Front::getInstance()->getHuiLang()->_('cdn.upload.files.error'));
        }
        
        $config=$this->getConfig();
        $miniHash=24;
        if ($config['hash_length']<$miniHash) {
            throw new Exception(Front::getInstance()->getHuiLang()->_('cdn.upload.hash_length.error', $miniHash));
        }
        $miniDepth=0;
        $maxDepth=5;
        if ($config['directory_depth']<$miniDepth || $config['directory_depth']>$maxDepth) {
            throw new Exception(Front::getInstance()->getHuiLang()->_('cdn.upload.directory_depth.error', $miniDepth, $maxDepth));
        }
        
        $hash=\HuiLib\Helper\Utility::geneRandomHash($config['hash_length']);
        $type=Param::post('type', Param::TYPE_STRING);
        $filePath=$config['save_path'].$type.SEP.date('Y').SEP;
        
        //每级包含的字母
        $charPerStep=2;
        for ($iter=0; $iter<$config['directory_depth']; $iter++){
            $filePath.=substr($hash, $iter*$charPerStep, $charPerStep).SEP;
        }
        
        //创建目录
        if (!is_dir($filePath)) {
            mkdir($filePath, 0777, TRUE);
        }
        
        $ext=$this->getExt($meta['name']);
        $file=$filePath.substr($hash, $iter*$charPerStep).$ext;
        
        $result=array();
        $result['file']=$file;
        $result['url']=str_ireplace(SEP, URL_SEP, str_ireplace($config['save_path'], '', $file));
        return $result;
    }
    
    /**
     * 生成一个头像储存路径
     * 
     * 需要将size替换为相应规格号
     */
    protected function getAvatarPath($meta)
    {
        if (empty($meta['size']) || empty($meta['type']) || empty($meta['name']) || empty($meta['uid'])) {
            throw new Exception(Front::getInstance()->getHuiLang()->_('cdn.upload.files.error'));
        }
        
        $uid=$meta['uid'];
        $config=$this->getConfig();
        $type=Param::post('type', Param::TYPE_STRING);
        
        //替换为10位，刚好10亿 1000,000,000
        $uidStr = sprintf ( "%010d",  $uid);
        $filePath=$config['save_path'].$type.SEP. substr ( $uidStr, 0, 4 ) . SEP . substr ( $uidStr, 4, 3 ) . SEP . substr ( $uidStr, 7, 3 ) . SEP;
        
        //创建目录
        if (!is_dir($filePath)) {
            mkdir($filePath, 0777, TRUE);
        }
        
        $ext=$this->getExt($meta['name']);
        $file=$filePath.'{size}'.$ext;
        
        $result=array();
        $result['file']=$file;
        $result['url']=str_ireplace(SEP, URL_SEP, str_ireplace($config['save_path'], '', $file));
        return $result;
    }
    
    protected function getExt($fileName){
        $ext=substr($fileName, strrpos($fileName, '.'));
        //不压缩png，直接转换成jpg；因为有很多同学上传的图片格式是png的，压缩效果非常差。1M多。
        if (strtolower($ext)=='.png') {
            $ext='.jpg';
        }
        return $ext;
    }
}
