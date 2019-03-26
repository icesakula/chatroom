<?php

namespace app\index\model;

use think\Db;
use think\Model;

class Redenveloplog extends Model
{
    public function send($money){
        $res = true;
        Db::startTrans();
        try{
            db('chatuser')->where('id',session('uid'))->setDec('money',$money);
            $money_info = [
                'user_id'     => session('uid'),
                'money'       => '-'.$money,
                'type'        => 2,
                'create_time' => time(),
            ];
            db('moneylog')->insert($money_info);
            $info = [
                'user_id'     => session('uid'),
                'money'       => $money,
                'create_time' => time(),
            ];
            $this->insert($info);
            $res = $this->getLastInsID();
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            $res = false;
            Db::rollback();
        }
        return $res;
    }

    public function receive($id){
        $res = true;
        Db::startTrans();
        try{
            $update_info = [
                'get_user_id' => session('uid'),
                'status'      => 1,
                'open_time'   => time(),
            ];
            $this->where('id',$id)->update($update_info);
            $money = $this->where('id',$id)->value('money');
            $money_info = [
                'user_id'     => session('uid'),
                'money'       => $money,
                'type'        => 3,
                'create_time' => time(),
            ];
            db('moneylog')->insert($money_info);
            db('chatuser')->where('id',session('uid'))->setInc('money',$money);
            //修改聊天记录中图片
            db('chatlog')->where('content','red['.$id.'|'.$money.'|/static/layui/images/red/wait.png]')->update(['content'=>'red['.$id.'|'.$money.'|/static/layui/images/red/gone.png]']);
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            $res = false;
            Db::rollback();
        }
        return $res;
    }

    public function transfer($id,$money){
        $res = true;
        Db::startTrans();
        try{
            db('chatuser')->where('id',session('uid'))->setDec('money',$money);
            $money_info = [
                'user_id'     => session('uid'),
                'money'       => '-'.$money,
                'type'        => 4,
                'create_time' => time(),
            ];
            db('moneylog')->insert($money_info);

            db('chatuser')->where('id',$id)->setInc('money',$money);
            $money_info1 = [
                'user_id'     => $id,
                'money'       => $money,
                'type'        => 5,
                'create_time' => time(),
            ];
            db('moneylog')->insert($money_info1);

            $info = [
                'user_id'     => session('uid'),
                'get_user_id' => $id,
                'money'       => $money,
                'status'      => 1,
                'is_transfer' => 1,
                'create_time' => time(),
                'open_time'   => time(),
            ];
            $this->insert($info);
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            $res = false;
            Db::rollback();
        }
        return $res;
    }
}