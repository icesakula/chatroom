<?php

namespace app\index\controller;

use app\index\model\ChatuserFriendrequest;
use think\Controller;

class Friend extends Controller
{
    //添加好友
    public function addFriend(){
        if($this->request->isAjax()){
            $phone = input('phone');
            $id    = input('id');
            if(empty($id)){
                if(empty($phone)){
                    return $this->error('请输入手机号码');
                }
                $user_info = db('chatuser')->where('phone',$phone)->find();
            }else{
                $user_info = db('chatuser')->find($id);
            }

            if(!$user_info){
                return $this->error('该用户不存在');
            }
            if($user_info['id'] == session('uid')){
                return $this->error('不能添加自己为好友');
            }
            if(db('chatuser_friend')->where(['user_id'=>$user_info['id'],'friend_id'=>session('uid')])->find()){
                return $this->error('您与对方已经是好友');
            }
            $request_info = db('chatuser_friendrequest')->where(['request_user_id'=>session('uid'),'user_id'=>$user_info['id']])->order('create_time','desc')->find();
            if(!empty($request_info) && $request_info['status']==0){
                return $this->error('您已发出申请，请等待对方回应');
            }
            $res = model('ChatuserFriendrequest')->addFriend($user_info['id'],session('uid'));
            if($res){
                return $this->success('好友申请已提交');
            }else{
                return $this->error('发生未知错误');
            }
        }else{
            return $this->fetch();
        }
    }

    //收到的好友请求列表
    public function requestList(){
        $list = db('ChatuserFriendrequest')
            ->where('user_id',session('uid'))
            ->where('status',0)
            ->select();
        $this->assign('list',$list);
        return $this->fetch();
    }

    //好友请求操作方法
    public function agree(){
        if($this->request->isAjax()){
            $id    = input('id');
            $reply = input('reply');
            $res = model('ChatuserFriendrequest')->reply($id,$reply);
            if($res){
                return $this->success('操作成功');
            }else{
                return $this->error('操作失败');
            }
        }
    }

}