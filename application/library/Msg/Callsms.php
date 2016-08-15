<?php
/**
 * 手机短信库
 */

class Msg_Callsms{

    //下面3个参数由云测短信提供（用户账号，密码，模板id）
    private static $user_apiKey = 'f16f3b5b038d77e9f2fcdbd3d80b7595';// String  用户账号
    private static $user_seckey = '4FDB3A6CFCC04F9C';	//String		//用户的密码
    private static $user_templateId = '1064';   //String 用户测试用的 ----短信模板ID
    private static $url='http://api.sms.testin.cn/sms';

    public static function setSendPostJsonStr($tel, $code){
        $data['apiKey'] =  self::$user_apiKey;
        $data['content'] = $code;
        $data['extNum'] = "";
        $data['op'] = "Sms.send";
        $data['phone'] = $tel;//群发号码间用英文逗号隔开，最多200个号码，例如：13911112222,13022221111,13311110000
        $data['taskId'] = floor((microtime(true)*1000)); ////不超过64位长度的唯一字符串，通过和手机状态接口获取的结果里的teskid关联，确定发送的信息是否收到。
        $data['templateId'] = self::$user_templateId;
        $data['ts'] = floor((microtime(true)*1000));
        $str = '';
        foreach ($data as $k => $v)
        {
            $str .= $k.'='.$v;
        }
        $str .=self::$user_seckey;
        $data['sig'] = md5($str);
        return json_encode($data);
    }

    public static function sendPostRequest($tel, $code)
    {
        $params = self::setSendPostJsonStr($tel, $code);
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, self::$url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_VERBOSE, '1');

        $user_agent = "Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)";
        //curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-Length: ' . strlen($params)) );

        curl_setopt($ch, CURLOPT_POSTFIELDS,$params);
        curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);//ץȡ��ת���ҳ��
        curl_setopt($ch, CURLOPT_TIMEOUT, 25);    // Timeout

        $return = curl_exec($ch);
        $return_array['STATUS'] = curl_getinfo($ch);
        $return_array['ERROR']  = curl_error($ch);
        $return_array['ERRNO'] = curl_errno($ch);
        curl_close($ch);
        if($return_array['ERRNO'] > 0 || $return_array['ERROR'])
        {
            $log_data = array(
                'POSTFIELDS' => $params,
                'return' =>var_export($return_array,TRUE)."\r\n"
            );
            // Toolkit::saveLogExt('sso', $log_data, array('msg' => 'sso login curl is error'));
            return FALSE;
        }

        return $return;
    }

}




/*


//下面3个参数由云测短信提供（用户账号，密码，模板id）

$user_apiKey = 'f16f3b5b038d77e9f2fcdbd3d80b7595';// String  用户账号
$user_seckey = '4FDB3A6CFCC04F9C';	//String		//用户的密码
$user_templateId = '1064';   //String 用户测试用的 ----短信模板ID

function setSendPostJsonStr()
{
    global $user_apiKey;
    global $user_seckey;
    global $user_templateId;

    $data['apiKey'] =  $user_apiKey;
    $data['content'] = "验证码是：5643";
    $data['extNum'] = "";
    $data['op'] = "Sms.send";
    $data['phone'] = "13910788133";//群发号码间用英文逗号隔开，最多200个号码，例如：13911112222,13022221111,13311110000
    $data['taskId'] = floor((microtime(true)*1000)); ////不超过64位长度的唯一字符串，通过和手机状态接口获取的结果里的teskid关联，确定发送的信息是否收到。
    $data['templateId'] = $user_templateId;
    $data['ts'] = floor((microtime(true)*1000));

    $str = '';
    foreach ($data as $k => $v)
    {
        $str .= $k.'='.$v;
    }
    $str .=$user_seckey;
    var_dump($str);
    $data['sig'] = md5($str);
    var_dump( md5($str));
    return json_encode($data);
}
// #接收手机回复，要轮询该接口，
function setMoPostJsonStr()
{
    global $user_apiKey;
    global $user_seckey;


    $data['apiKey'] =  $user_apiKey;
    $data['op'] = "Sms.mo";
    $data['ts'] = floor((microtime(true)*1000));


    //array_multisort($data,SORT_ASC);
    $str = '';
    foreach ($data as $k => $v)
    {
        $str .= $k.'='.$v;
    }$str .=$user_seckey;
    var_dump($str);
    $data['sig'] = md5($str);
    var_dump( md5($str));
    return json_encode($data);
}
//接收手机是否收到的状态，要轮询该接口，
function setRptPostJsonStr()
{
    global $user_apiKey;
    global $user_seckey;



    $data['apiKey'] =  $user_apiKey;
    $data['op'] = "Sms.status";
    $data['ts'] = floor((microtime(true)*1000));


    //array_multisort($data,SORT_ASC);
    $str = '';
    foreach ($data as $k => $v)
    {
        $str .= $k.'='.$v;
    }$str .=$user_seckey;
    var_dump($str);
    $data['sig'] = md5($str);
    var_dump( md5($str));
    return json_encode($data);
}
//查询余额
function setBalPostJsonStr()
{
    global $user_apiKey;
    global $user_seckey;


    $data['apiKey'] =  $user_apiKey;
    $data['op'] = "Sms.account";
    $data['ts'] = floor((microtime(true)*1000));


    //array_multisort($data,SORT_ASC);
    $str = '';
    foreach ($data as $k => $v)
    {
        $str .= $k.'='.$v;
    }$str .=$user_seckey;
    var_dump($str);
    $data['sig'] = md5($str);
    var_dump( md5($str));
    return json_encode($data);
}
function sendPostRequest($url='http://api.sms.testin.cn/sms')
{
    $params = setSendPostJsonStr();
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL,$url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_VERBOSE, '1');

    $user_agent = "Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)";
    //curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-Length: ' . strlen($params)) );

    curl_setopt($ch, CURLOPT_POSTFIELDS,$params);
    curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);//ץȡ��ת���ҳ��
    curl_setopt($ch, CURLOPT_TIMEOUT, 25);    // Timeout

    $return = curl_exec($ch);
    $return_array['STATUS'] = curl_getinfo($ch);
    $return_array['ERROR']  = curl_error($ch);
    $return_array['ERRNO'] = curl_errno($ch);
    curl_close($ch);
    if($return_array['ERRNO'] > 0 || $return_array['ERROR'])
    {
        $log_data = array(
            'POSTFIELDS' => $params,
            'return' =>var_export($return_array,TRUE)."\r\n"
        );
        // Toolkit::saveLogExt('sso', $log_data, array('msg' => 'sso login curl is error'));
        return FALSE;
    }

    return $return;
}
function moPostRequest($url='http://api.sms.testin.cn/sms')
{
    $params = setMoPostJsonStr();
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL,$url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_VERBOSE, '1');

    $user_agent = "Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)";
    //curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-Length: ' . strlen($params)) );

    curl_setopt($ch, CURLOPT_POSTFIELDS,$params);
    curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);//ץȡ��ת���ҳ��
    curl_setopt($ch, CURLOPT_TIMEOUT, 25);    // Timeout

    $return = curl_exec($ch);
    $return_array['STATUS'] = curl_getinfo($ch);
    $return_array['ERROR']  = curl_error($ch);
    $return_array['ERRNO'] = curl_errno($ch);
    curl_close($ch);
    if($return_array['ERRNO'] > 0 || $return_array['ERROR'])
    {
        $log_data = array(
            'POSTFIELDS' => $params,
            'return' =>var_export($return_array,TRUE)."\r\n"
        );
        // Toolkit::saveLogExt('sso', $log_data, array('msg' => 'sso login curl is error'));
        return FALSE;
    }

    return $return;
}
function rptPostRequest($url='http://api.sms.testin.cn/sms')
{
    $params = setRptPostJsonStr();
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL,$url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_VERBOSE, '1');

    $user_agent = "Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)";
    //curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-Length: ' . strlen($params)) );

    curl_setopt($ch, CURLOPT_POSTFIELDS,$params);
    curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);//ץȡ��ת���ҳ��
    curl_setopt($ch, CURLOPT_TIMEOUT, 25);    // Timeout

    $return = curl_exec($ch);
    $return_array['STATUS'] = curl_getinfo($ch);
    $return_array['ERROR']  = curl_error($ch);
    $return_array['ERRNO'] = curl_errno($ch);
    curl_close($ch);
    if($return_array['ERRNO'] > 0 || $return_array['ERROR'])
    {
        $log_data = array(
            'POSTFIELDS' => $params,
            'return' =>var_export($return_array,TRUE)."\r\n"
        );
        // Toolkit::saveLogExt('sso', $log_data, array('msg' => 'sso login curl is error'));
        return FALSE;
    }

    return $return;
}
function balPostRequest($url='http://api.sms.testin.cn/sms')
{
    $params = setBalPostJsonStr();
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL,$url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_VERBOSE, '1');

    $user_agent = "Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)";
    //curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-Length: ' . strlen($params)) );

    curl_setopt($ch, CURLOPT_POSTFIELDS,$params);
    curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);//ץȡ��ת���ҳ��
    curl_setopt($ch, CURLOPT_TIMEOUT, 25);    // Timeout

    $return = curl_exec($ch);
    $return_array['STATUS'] = curl_getinfo($ch);
    $return_array['ERROR']  = curl_error($ch);
    $return_array['ERRNO'] = curl_errno($ch);
    curl_close($ch);
    if($return_array['ERRNO'] > 0 || $return_array['ERROR'])
    {
        $log_data = array(
            'POSTFIELDS' => $params,
            'return' =>var_export($return_array,TRUE)."\r\n"
        );
        // Toolkit::saveLogExt('sso', $log_data, array('msg' => 'sso login curl is error'));
        return FALSE;
    }

    return $return;
}
*/