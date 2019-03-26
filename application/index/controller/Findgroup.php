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

class Findgroup extends Controller
{
    //显示查询 / 添加 分组的页面
    public function index()
    {
        $groupArr = db('chatgroup')->order('id desc')->limit(4)->select();
        $this->assign([
            'group' => $groupArr
        ]);
        return $this->fetch();
    }

    //搜索查询群组
    public function search()
    {
        $groupname = input('param.search_txt');
        $find = db('chatgroup')->where("groupname like '%" . $groupname . "%'")->select();

        if( empty($find) ){
            return json( ['code' => -1, 'data' => '', 'msg' => '您搜的群不存在' ] );
        }

        return json( ['code' => 1, 'data' => $find, 'msg' => 'success' ] );
    }
    
    //加入群组
    public function joinGroup()
    {
    	$groupid = input('param.gid');
    	$has = db('chatgroup')->where('id = ' . $groupid)->find();
    	
    	if( empty( $has ) ){
    		return json( ['code' => -1, 'data' => '', 'msg' => '该群组不存在' ] );
    	}
    	
    	$uid = session('uid');
    	//已经加入了
    	$allready = db('groupdetail')->field('userid')
    	->where('groupid = ' . $groupid . ' and userid = ' . $uid)
    	->find();
    	
    	if( !empty( $allready ) ){
    		return json( ['code' => -2, 'data' => '', 'msg' => '你已经加入该群了' ] );
    	}
    	
    	$param = [
    			'userid' => $uid,
    			'username' => session('username'),
    			'useravatar' => session('avatar'),
    			'usersign' => session('sign'),
    			'groupid' => $groupid
    	];
    	
    	db('groupdetail')->insert( $param );

        //socket data
        $join_data = '{"type":"joinGroup", "data" : {"avatar":"' . $has['avatar'] . '","groupname":"' . $has['groupname'] . '",';
        $join_data .= '"id":"' . $groupid. '", "uid":"' . $uid . '"}}';
    	
    	return json( ['code' => 1, 'data' => $join_data, 'msg' => '成功加入' ] );
    }

    //添加群组
    public function addGroup()
    {
    	if( empty(session('uid')) ){
    		$this->redirect( url('index/index') );
    	}
    	
    	if( request()->isPost() ){
    		
    		$param = input('post.');
    		$ids = $param['ids'];
    		
    		unset( $param['ids'] );
    		
    		if( empty($param['groupname']) ){
    			return json( ['code' => -1, 'data' => '', 'msg' => '群组名不能为空' ] );
    		}
    		
    		if( empty( $ids ) ){
    			return json( ['code' => -2, 'data' => '', 'msg' => '请添加成员' ] );
    		}
    		
    		$this->_getUpFile( $param );
    		
    		$param['owner_name'] = session('username');
    		$param['owner_id'] = session('uid');
    		$param['owner_avatar'] = session('avatar');
    		$param['owner_sign'] = session('sign');
    		
    		$flag = db('chatgroup')->insert( $param );
    		if( empty( $flag ) ){
    			return json( ['code' => -3, 'data' => '', 'msg' => '添加群组失败' ] );
    		}
    		
    		//unset( $param );
    		//拼装上自己
    		$ids .= "," . session('uid');
    		$groupid = db('chatgroup')->getLastInsID();

    		$users = db('chatuser')->where("id in($ids)")->select();
    		if( !empty( $users ) ){
    			foreach( $users as $key=>$vo ){
    				
    				$params = [
    						'userid' => $vo['id'],
    						'username' => $vo['username'],
    						'useravatar' => $vo['avatar'],
    						'usersign' => $vo['sign'],
    						'groupid' => $groupid
    				];
    				
    				db('groupdetail')->insert( $params );
    				unset( $params );
    			}
    		}

			//socket data
			$add_data = '{"type":"addGroup", "data" : {"avatar":"' . $param['avatar'] . '","groupname":"' . $param['groupname'] . '",';
			$add_data .= '"id":"' . $groupid. '", "uids":"' . $ids . '"}}';
    		
    		return json( ['code' => 1, 'data' => $add_data, 'msg' => '创建群组 成功' ] );
    	}
    	
        return $this->fetch();
    }
    
    //管理我的群组
    public function myGroup()
    {
    	
    	if( request()->isAjax() ){
    		$groupid = input('param.id');
    		$users = db('groupdetail')->field('username,userid,useravatar,groupid')->where('groupid', $groupid)->select();
    		
    		return json( ['code' => 1, 'data' => $users, 'msg' => 'success'] );
    	}
    	
    	$group = [];
    	$users = [];
    	$group = db('chatgroup')->field('id,groupname')->where(['owner_id'=> session('uid'),'id'=>input('param.id')])->select();
    	if( !empty($group) ){
    	    $id = $group['0']['id'];
    	    if(input('param.id')){
    	        $id = input('param.id');
            }
    		$users = db('groupdetail')->field('username,userid,useravatar,groupid')->where('groupid', $id)->select();
    	}
    	
    	$this->assign([
    			'group' => $group,
    			'users' => $users
    			
    	]);
    	return $this->fetch();
    }
    
    //追加群组人员
    public function addMembers()
    {
    	$groupid = input('param.gid');
    	$ids = input('param.ids');
    	$users = db('chatuser')->where("id in($ids)")->select();
    	if( !empty( $users ) ){
    		foreach( $users as $key=>$vo ){
    	
    			$param = [
    					'userid' => $vo['id'],
    					'username' => $vo['username'],
    					'useravatar' => $vo['avatar'],
    					'usersign' => $vo['sign'],
    					'groupid' => $groupid
    			];
    	
    			db('groupdetail')->insert( $param );
    			unset( $param );
    		}
    	}

        $group = db('chatgroup')->field('avatar,groupname')->where('id', $groupid)->find();
        //socket data
        $add_data = '{"type":"addMember", "data" : {"avatar":"' . $group['avatar'] . '","groupname":"' . $group['groupname'] . '",';
        $add_data .= '"id":"' . $groupid. '", "uid":"' . $ids . '"}}';
    	
    	return json( ['code' => 1, 'data' => $add_data, 'msg' => '加入群组 成功' ] );
    }
    
    //移出成员出组
    public function removeMembers()
    {
    	$uid = input('param.uid');
    	$groupid = input('param.gid');
    	
    	$cannot = db('chatgroup')->field('id')->where('owner_id = ' . $uid . ' and id = ' . $groupid)->find();
    	if( !empty( $cannot ) ){
    		return json( ['code' => -1, 'data' => '', 'msg' => '不可移除群主'] );
    	}
    	
    	db('groupdetail')->where('userid = ' . $uid . ' and groupid = ' .$groupid)->delete();
    	
    	return json( ['code' => 1, 'data' => '', 'msg' => '移除成功'] );
    }
    
    //解散群组
    public function removeGroup()
    {
    	$groupid = input('param.gid');
    	//删除群组
    	db('chatgroup')->where('id', $groupid)->delete();
    	
    	//删除群成员
    	db('groupdetail')->where('groupid', $groupid)->delete();
    	
    	return json( ['code' => 1, 'data' => '', 'msg' => '成功解散该群'] );
    }
    
    //获取所有的用户
    public function getUsers()
    {
        $friend_id_arr = db('chatuser_friend')->where('user_id',session('uid'))->column('friend_id');
    	$result = db('chatuser')->field('id,username,groupid')
    	->where('id','in',$friend_id_arr)
    	->select();
    	
    	if( empty($result) ){
    		return json( ['code' => -1, 'data' => '', 'msg' => '暂无其他成员'] );
    	}
    	
    	$str = "";
    	$flag = input('param.flag');
    	$flag = empty( $flag ) ? false : true;
    	if( $flag ){
    		//查询该分组中的成员id
    		$groupid = input('param.gid');
    		$ids = db('groupdetail')->field('userid')->where('groupid', $groupid)->select();
    		
    		if( !empty( $ids ) ){
    			foreach( $ids as $key=>$vo ){
    				$idsArr[] = $vo['userid'];
    			}
    			unset( $ids );
    		}
    		
    		foreach( $result as $key=>$vo ){
    			if( in_array( $vo['id'], $idsArr ) ){
    				unset( $result[$key] );
    			}
    		}
    	}
    	
    	if( empty($result) ){
    		return json( ['code' => -2, 'data' => '', 'msg' => '该群组已经包含了全部成员'] );
    	}
    	
    	$group = config('user_group');
    	//先将默认分组拼装好
    	foreach( $group as $key=>$vo ){
    		$str .= '{ "id": "-' . $key . '", "pId":0, "name":"' . $vo .'"},';
    	}
    	
    	foreach($result as $key=>$vo){
    		$str .= '{ "id": "' . $vo['id'] . '", "pId":"-' . $vo['groupid'] . '", "name":"' . $vo['username'].'"},';
    	}
    	
    	$str = "[" . substr($str, 0, -1) . "]";
    	
    	return json( ['code' => 1, 'data' => $str, 'msg' => 'success'] );
    }
    
    /**
     * 上传图片方法
     * @param $param
     */
    private function _getUpFile(&$param)
    {
    	// 获取表单上传文件
    	$file = request()->file('avatar');
    
    	// 移动到框架应用根目录/public/uploads/ 目录下
    	if( !is_null( $file ) ){
    
    		$info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');
    		if($info){
    			// 成功上传后 获取上传信息
    			$param['avatar'] =  '/uploads' . '/' . date('Ymd') . '/' . $info->getFilename();
    		}else{
    			// 上传失败获取错误信息
    			echo $file->getError();
    		}
    	}else{
    		unset( $param['avatar'] );
    	}
    
    }


    public function checkgroup($id){
        if($this->request->isAjax()){
            if($id == session('uid')){
                return $this->error('不能添加自己为好友');
            }
            if(db('chatuser_friend')->where(['user_id'=>$id,'friend_id'=>session('uid')])->find()){
                return $this->error('您与对方已经是好友');
            }
            $request_info = db('chatuser_friendrequest')->where(['request_user_id'=>session('uid'),'user_id'=>$id])->order('create_time','desc')->find();
            if(!empty($request_info) && $request_info['status']==0){
                return $this->error('您已发出申请，请等待对方回应');
            }
            $res = model('ChatuserFriendrequest')->addFriend($id,session('uid'));
            if($res){
                return $this->success('好友申请已提交');
            }else{
                return $this->error('发生未知错误');
            }
        }
        $owner_id = db('chatgroup')->where('id',$id)->value('owner_id');
        if($owner_id == session('uid')){
            $this->redirect('/index/findgroup/mygroup/id/'.input('id'));
        }
        $group = [];
        $users = [];
        $group[] = db('chatgroup')->field('id,groupname')->where('id', $id)->find();
        if( !empty($group) ){
            $users = db('groupdetail')->field('username,userid,useravatar,groupid')->where('groupid', $group['0']['id'])->select();
        }

        $this->assign([
            'group' => $group,
            'users' => $users

        ]);
        return $this->fetch();
    }

    public function setNickname()
    {
        $id       = input('id');
        $nickname = input('name');
        $group_id = input('group_id');
        if($this->request->isPost()){
            $info = [
                'user_id'=>session('uid'),
                'nick_id'=>$id,
                'group_id'=>$group_id,
                'nickname'=>$nickname,
            ];
            $res = model('nickname')->saveNickname($info);
            return $this->success('修改成功');
        }else{
            $this->assign('id', $id);
            $this->assign('nickname', $nickname);
            $this->assign('group_id', $group_id);
            return $this->fetch();
        }
    }
    public function findNickname(){
        $where = [
            'nick_id'   => input('id'),
            'group_id'  => input('group_id'),
        ];
        $info = db('nickname')->where($where)->find();
        if($info){
            return $this->success($info['nickname']);
        }else{
            return $this->success('');
        }
    }
}