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

use think\Controller;

class Chatlog extends Controller
{
    //聊天记录
    public function index()
    {
        $id = input('id');
        $type = input('type');

        $this->assign([
            'id' => $id,
            'type' => $type
        ]);

        return $this->fetch();
    }

    //聊天记录详情
    public function detail()
    {
        $id = input('id');
        $type = input('type');

        $uid = session('uid');

        if( 'friend' == $type ){
            $result = db('chatlog')->where("((fromid={$uid} and toid={$id}) or (fromid={$id} and toid={$uid})) and type='friend'")
                ->order('timeline desc')
                ->select();

            if( empty($result) ){
                return json( ['code' => -1, 'data' => '', 'msg' => '没有记录'] );
            }

            return json( ['code' => 1, 'data' => $result, 'msg' => 'success'] );
        }else if('group' == $type){

            $result = db('chatlog')->where("toid={$id} and type='group'")
                ->order('timeline desc')
                ->select();
            
            if( empty($result) ){
                return json( ['code' => -1, 'data' => '', 'msg' => '没有记录'] );
            }

            return json( ['code' => 1, 'data' => $result, 'msg' => 'success'] );
        }
    }

    //聊天记录返回为前端需要的json格式
    public function chatlogJson()
    {
        if($this->request->isAjax()){
            $user_id = input('id');
            //好友聊天记录
            $friend = db('chatlog')
                ->where("(fromid={$user_id} or toid={$user_id}) and type='friend'")
                ->order('timeline asc')
                ->select();
            $result = [];
            $info   = [];
            foreach($friend as $v){
                if($v['fromid'] == $user_id){
                    $index = $v['toid'];
                    $info['mine']=true;
                }else{
                    $index = $v['fromid'];
                }
                $info['avatar']    = $v['fromavatar'];
                $info['content']   = $v['content'];
                $info['id']        = $index;
                $info['timestamp'] = $v['timeline']*1000;
                $info['type']      = $v['type'];
                $info['username']  = $v['fromname'];
                $result['friend'.$index][] = $info;
                $info   = [];
            }
            //群组聊天记录
            $group_id = db('groupdetail')->where('userid',$user_id)->column('groupid');
            $group_id_str = implode(',',$group_id);
            $group = db('chatlog')
                ->where("toid in ({$group_id_str}) and type='group'")
                ->order('timeline asc')
                ->select();

            foreach($group as $v){
                //获取群组的昵称数据
                $nickname = db('nickname')->where('group_id',$v['toid'])->select();
                $nickname_arr = [];
                foreach ($nickname as $v1){
                    $nickname_arr[$v1['nick_id']] = $v1['nickname'];
                }
                if($v['fromid'] == $user_id){
                    $info['mine']=true;
                }
                $info['avatar']    = $v['fromavatar'];
                $info['content']   = $v['content'];
                $info['id']        = $v['fromid'];
                $info['timestamp'] = $v['timeline']*1000;
                $info['type']      = $v['type'];
                //替换备注名称
                if(isset($nickname_arr[$v['fromid']])){
                    $info['username']  = $nickname_arr[$v['fromid']];
                }else{
                    $info['username']  = $v['fromname'];
                }
                $result['group'.$v['toid']][] = $info;
                $info   = [];
            }
            return $this->success($result);
        }
    }

    //历史记录返回为前端需要的json格式
    public function historyJson(){
        $user_id = input('id');
        //好友聊天记录
        $friend = db('chatlog')
            ->where("(fromid={$user_id} or toid={$user_id}) and type='friend'")
            ->order('timeline desc')
            ->select();
        $result = [];
        $info   = [];
        foreach($friend as $v){
            if($v['fromid'] == $user_id){
                $index = $v['toid'];
            }else{
                $index = $v['fromid'];
            }
            if(empty($result['friend'.$index])){
                $user = db('chatuser')->find($index);
                $info['avatar']      = $user['avatar'];
                if(substr($v['content'],0,4)=='red['){
                    $v['content']     = '[红包]';
                }
                if(substr($v['content'],0,9)=='transfer['){
                    $v['content']     = '[转账]';
                }
                $info['content']     = $v['content'];
                $info['sign']        = $v['content'];
                $info['id']          = $index;
                $info['timestamp']   = $v['timeline']*1000;
                $info['historyTime'] = $v['timeline']*1000;
                $info['type']        = $v['type'];
                $info['username']    = $user['username'];
                $info['name']        = $user['username'];
                $result['friend'.$index] = $info;
            }
            $info   = [];
        }
        //群组聊天记录
        $group_id = db('groupdetail')->where('userid',$user_id)->column('groupid');
        $group_id_str = implode(',',$group_id);
        $group = db('chatlog')
            ->where("toid in ({$group_id_str}) and type='group'")
            ->order('timeline desc')
            ->select();
        foreach($group as $v){
            if(empty($result['group'.$v['toid']])){
                $group_info = db('chatgroup')->find($v['toid']);
                $info['avatar']      = $group_info['avatar'];
                if(substr($v['content'],0,4)=='red['){
                    $v['content']     = '[红包]';
                }
                if(substr($v['content'],0,9)=='transfer['){
                    $v['content']     = '[转账]';
                }
                $info['content']     = $v['content'];
                $info['sign']        = $v['content'];
                $info['id']          = $v['toid'];
                $info['timestamp']   = $v['timeline']*1000;
                $info['historyTime'] = $v['timeline']*1000;
                $info['type']        = $v['type'];
                $info['username']    = $group_info['groupname'];
                $info['name']        = $group_info['groupname'];
                $result['group'.$v['toid']] = $info;
            }
            $info   = [];
        }
        return $this->success($result);
    }
}