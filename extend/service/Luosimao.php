<?php
/**
 * Description: Luosimao.php.
 *
 * @author      HuQi
 * @datetime    2017/4/29
 * @copyright   WuHan HuQi Technology Co.,Ltd.
 * @url         http://www.cnhuqi.com
 */

namespace service;

use think\Exception;

final class Luosimao
{
    /**
     * 接口地址
     */
    const SMS_INTERFACE_URL = 'http://sms-api.luosimao.com/v1/send.json';

    /**
     * 设置
     * @var mixed|string
     */
    private $setting = '';

    private $appkey = 'b57b5d3e5d8e658b37b7f51bdacf0b2e';

    private $template = '';

    /**
     * 初始化
     * @throws Exception
     */
    public function __construct()
    {

        $this->setting = config('sms');
        if (empty($this->setting)) {
            throw new Exception('短信配置错误');
        }
        $this->appkey = $this->setting['secret'];
        $this->template = $this->setting['template']['valid'];
    }

    /**
     * 短信发送
     * @param string $mobile
     * @param string $val
     * @return bool
     */
    public function send ($mobile = '', $val = '')
    {
        $this->template = str_replace('{code}', $val, $this->template);
        $res = $this->_send_message(array(
            'mobile'    => $mobile,
            'message'   => $this->template
        ));

        $smsResult = json_decode($res, true);
        return (isset($smsResult['msg']) && $smsResult['msg'] == 'ok') ? true : false;
    }

    /**
     * 发送文本信息
     * @param $mobile
     * @param string $message
     * @return bool
     */
    public function message($mobile, $message = '')
    {
        $res = $this->_send_message(array(
            'mobile' => $mobile,
            'message' => $message
        ));

        $smsResult = json_decode($res, true);
        return (isset($smsResult['msg']) && $smsResult['msg'] == 'ok') ? true : false;
    }

    /**
     * sms interface
     * @param $params
     * @return mixed
     */
    public function _send_message($params)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, self::SMS_INTERFACE_URL);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, 'api:key-' . $this->appkey);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        $res = curl_exec($ch);
        curl_close($ch);

        return $res;
    }
}