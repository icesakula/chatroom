<?php
namespace app\index\controller;
use service\Luosimao;
use think\Cache;
use think\Controller;

class Message extends Controller
{
    public function _initialize()
    {
        if(Cache::get(getip())>=100){
            $this->error('访问次数过多,请稍后再试');
        }
    }
    public function sendMessage($phone,$word)
    {
        $code = rand(100000,999999);
        $luosimao = new Luosimao();
        $cache = new Cache();
        $cache::set($phone.$word,$code,600);
        //记录ip访问次数
        $ip = getip();
        if($cache::get($ip)){
            $cache::set($ip,$cache::get($ip)+1,3600);
        }else{
            $cache::set($ip,1,3600);
        }

        $data['mobile'] = $phone;
        switch ($word){
            case 'resetPassword';
                $data['message'] = '您在找回密码，验证码为'.$code.'【乐闲清来】';
                break;
            case 'register';
                $data['message'] = '您在注册聊天室账号，验证码为'.$code.'【乐闲清来】';
                break;
        }
        $luosimao->_send_message($data);
    }
}