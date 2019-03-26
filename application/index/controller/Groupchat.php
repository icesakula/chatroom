<?php

namespace app\index\controller;

use think\Controller;

class Groupchat extends Controller
{
    public function addGroup($name)
    {
        //socket data
        $add_data = '{"type":"addGroup", "data" : {type: "group" ,avatar: "/static/layui/images/head.pn" ,groupname: "'.$name.'",id: "1"}}';
        return json( ['code' => 1, 'data' => $add_data, 'msg' => '添加群聊成功'] );
    }
}