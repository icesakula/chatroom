<?php
namespace app\index\controller;

use app\admin\model\Chatuser;
use think\Controller;

class Personal extends Controller
{
    public function edituser(){
        if( request()->isPost() ){

            $param = input('post.');

            if ( empty($param['username']) ){
                return json( ['code' => -1, 'data' => '', 'msg' => '用户名不能为空'] );
            }


            if ( empty($param['sign']) ){
                return json( ['code' => -3, 'data' => '', 'msg' => '个性签名不能为空'] );
            }

            $this->_getUpFile( $param );  //处理上传头像

            $flag = (new Chatuser())->editUser( $param );
            if( 0 == $flag['code'] ){
                return json( ['code' => -5, 'data' => '', 'msg' => '编辑用户失败'] );
            }

            return json( ['code' => 1, 'data' => '', 'msg' => '编辑用户成功'] );
        }
        $user = db('chatuser')->find(session('uid'));
        $this->assign('user',$user);
        return $this->fetch();
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
}