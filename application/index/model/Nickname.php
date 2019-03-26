<?php
namespace app\index\model;

use think\Db;
use think\Model;

class Nickname extends Model
{
    public function saveNickname($info){
        $res = $this->where(['nick_id'=>$info['nick_id'],'group_id'=>$info['group_id']])->find();
        if($res){
            $result = $this->where('id',$res['id'])->update($info);
        }else{
            $result = $this->insert($info);
        }
        return $result;
    }
}
