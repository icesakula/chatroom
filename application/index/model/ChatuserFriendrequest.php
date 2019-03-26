<?php

namespace app\index\model;

use think\Db;
use think\Model;

class ChatuserFriendrequest extends Model
{
    //添加好友请求
    public function addFriend($user_id,$request_user_id){
        $info = [
            'user_id'           =>  $user_id,
            'request_user_id'   =>  $request_user_id,
            'request_user_name' =>  db('chatuser')->where('id',$request_user_id)->value('username'),
            'status'            =>  0,
            'create_time'       =>  time(),
            'update_time'       =>  time(),
        ];

        if($this->save($info)){
            return true;
        }else{
            return false;
        }
    }

    //好友请求处理
    public function reply($id,$reply){
        if($reply){
            $res = true;
            $info = $this->find($id);
            Db::startTrans();
            try{
                if(!db('chatuser_friend')->where(['user_id'=>$info['user_id'],'friend_id'=>$info['request_user_id']])->find()){
                    $data = [
                        ['user_id'=>$info['user_id'],'friend_id'=>$info['request_user_id']],
                        ['user_id'=>$info['request_user_id'],'friend_id'=>$info['user_id']],
                    ];
                    db('chatuser_friend')->insert($data[0]);
                    db('chatuser_friend')->insert($data[1]);
                }
                $this->where('id',$id)->update(['status'=>1,'update_time'=>time()]);
                // 提交事务
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                $res = false;
                Db::rollback();
            }
        }else{
            $res = $this->where('id',$id)->update(['status'=>2,'update_time'=>time()]);
        }
        if($res){
            return true;
        }
    }
}