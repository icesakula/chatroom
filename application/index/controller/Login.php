<?php
// +----------------------------------------------------------------------
// | layerIM + Workerman + ThinkPHP5 即时通讯
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2012 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: NickBai <1902822973@qq.com>
// +----------------------------------------------------------------------
namespace app\index\controller;

use think\Cache;
use think\Controller;
use think\Request;

class Login extends Controller
{
    public function index()
    {
        return $this->fetch();
    }
    
    public function doLogin()
    {
    	$uname = input('param.username');
    	$userinfo = db('chatuser')->where('phone', $uname)->find();

    	if( empty($userinfo) ){
    		$this->error("用户不存在");
    	}

        $pwd = input('param.pwd');
		if( md5($pwd) != $userinfo['pwd'] ){
            $this->error("密码不正确");
        }
    	
    	//设置为登录状态
    	db('chatuser')->where('phone', $uname)->setField('status', 'online');
    	
    	cookie( 'uid', $userinfo['id'] );
    	cookie( 'username', $userinfo['username'] );
        cookie( 'avatar', $userinfo['avatar'] );
        cookie( 'sign', $userinfo['sign'] );
        session( 'uid', $userinfo['id'] );
        session( 'username', $userinfo['username'] );
        session( 'avatar', $userinfo['avatar'] );
        session( 'sign', $userinfo['sign'] );
    	$this->redirect(url('index/index'));
    }

    //注册
    public function register(Request $request){
        if($request->isAjax()){
            $username = $request->param('username');
            $pwd      = $request->param('pwd');
            $code     = $request->param('code');
            $phone    = $request->param('phone');
            if($pwd==''||$username==''){
                return $this->error('请输入用户名和密码');
            }
            if(strlen($pwd)<6){
                return $this->error('请输入6位以上密码');
            }
            if($phone==''){
                return $this->error('请输入手机号码');
            }
            if($code==''){
                return $this->error('请输入验证码');
            }
            if(db('chatuser')->where('phone',$phone)->find()){
                return $this->error('该手机号码已被注册');
            }
            if($code != Cache::get($phone.'register')){
                return $this->error('验证码错误');
            }

            //删除验证码缓存
            Cache::rm($phone.'register');
            db('chatuser')->insert(['username'=>$username,'pwd'=>md5($pwd),'phone'=>$phone,'avatar'=>'/static/layui/images/head.png']);
            return $this->success('注册成功',url('login/index'));
        }else{
            return $this->fetch();
        }
    }

    //发送注册验证码
    public function sendMessageRegister(){
        if($this->request->isAjax()){
            $phone = input('phone');
            (new Message())->sendMessage($phone,'register');
        }else{
            return $this->error('非法访问');
        }
    }

    //找回密码
    public function resetPassword(Request $request){
        if($request->isAjax()){
            $pwd      = $request->param('pwd');
            $code     = $request->param('code');
            $phone    = $request->param('phone');
            if($pwd==''){
                return $this->error('请输入新密码');
            }
            if(strlen($pwd)<6){
                return $this->error('请输入6位以上密码');
            }
            if($phone==''){
                return $this->error('请输入手机号码');
            }
            if($code==''){
                return $this->error('请输入验证码');
            }
            if(!db('chatuser')->where('phone',$phone)->find()){
                return $this->error('该手机号码未注册过账号');
            }
            if($code != Cache::get($phone.'resetPassword')){
                return $this->error('验证码错误');
            }
            db('chatuser')->where('phone',$phone)->update(['pwd'=>md5($pwd)]);
            return $this->success('重置密码成功',url('login/index'));
        }else{
            return $this->fetch();
        }
    }
    //发送找回密码验证码
    public function sendMessageResetPassword(){
        if($this->request->isAjax()){
            $phone = input('phone');
            (new Message())->sendMessage($phone,'resetPassword');
        }else{
            return $this->error('非法访问');
        }
    }

    //退出登录
    public function logout(){
        cookie( 'uid', null );
        cookie( 'username', null );
        cookie( 'avatar', null );
        cookie( 'sign', null );
        session( 'uid', null );
        session( 'username', null );
        session( 'avatar', null );
        session( 'sign', null );
        $this->redirect('login/index');
    }
}
