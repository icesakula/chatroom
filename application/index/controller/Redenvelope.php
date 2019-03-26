<?php

namespace app\index\controller;

use app\index\model\Moneylog;
use app\index\model\Redenveloplog;
use think\Controller;

class Redenvelope extends Controller
{
    public function sendRed($money){
        if($this->request->isPost()){
            $user_money = db('chatuser')->where('id',session('uid'))->value('money');
            if($user_money<$money){
                $this->error('余额不足，请充值');
            }
            $res = (new Redenveloplog())->send($money);
            if($res){
                return $this->success($res);
            }else{
                return $this->error('发生未知错误');
            }
        }
    }

    public function receiveRed($id){
        if($this->request->isPost()){
            $status = db('redenveloplog')->where('id',$id)->value('status');
            if($status==1){
                return $this->error('红包已被领取');
            }
            $res = (new Redenveloplog())->receive($id);
            if($res){
                return $this->success('成功');
            }else{
                return $this->error('失败');
            }
        }
    }

    public function isopen($id){
        if($this->request->isPost()){
            $info = db('redenveloplog')->find($id);
            $name = db('chatuser')->where('id',$info['get_user_id'])->value('username');
            if($info['status']==1){
                return $this->error('红包已被'.$name.'领取');
            }else{
                return $this->success('');
            }

        }
    }

    //转账
    public function transferMoney(){
        if($this->request->isAjax()){
            if(!session('uid')){
                $this->error('非法访问');
            }
            $id    = input('id');
            $money = input('money');
            $info = db('chatuser')->where('id',$id)->find();
            $user_money = db('chatuser')->where('id',session('uid'))->value('money');
            if($user_money<$money){
                $this->error('余额不足，请充值');
            }
            $res = (new Redenveloplog())->transfer($id,$money);
            if($res){
                return $this->success('转账成功','',$info['username']);
            }else{
                return $this->error('发生未知错误');
            }
        }

    }
}