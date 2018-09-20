<?php
if(!defined('IN_COOLQ')) {
	exit('Access Denied');
}
class CQ{
    static function init(){
        global $cq_config;
        $getData = $GLOBALS['HTTP_RAW_POST_DATA']?$GLOBALS['HTTP_RAW_POST_DATA']: file_get_contents('php://input');
        $getData = json_decode($getData, true);
        $getData = cqmsg($getData);
        $events = array(
            'message' => array(
                'private'=> array('func'=>'_event_PrivateMsg','params'=>'sub_type,message_id,user_id,message'),
                'group'=> array('func'=>'_event_GroupMsg','params'=>'sub_type,message_id,group_id,user_id,anonymous,message'),
                'discuss'=> array('func'=>'_event_DiscussMsg','params'=>'message_id,discuss_id,user_id,message'),
            ),
            'notice' => array(
                'group_upload'=> array('func'=>'_event_GroupFileUpload','params'=>'group_id,user_id,file'),
                'group_admin'=> array('func'=>'_event_GroupAdminChange','params'=>'sub_type,group_id,user_id'),
                'group_decrease'=> array('func'=>'_event_GroupMemberDecrease','params'=>'sub_type,group_id,operator_id,user_id'),
                'group_increase'=> array('func'=>'_event_GroupMemberIncrease','params'=>'sub_type,group_id,operator_id,user_id'),
                'friend_add'=>array('func'=>'_event_FriendIsAdd','params'=>'user_id'),
            ),
            'request'=> array(
                'friend'=>array('func'=>'_event_RequestAddFriend','params'=>'user_id,comment,flag'),
                'group'=>array('func'=>'_event_RequestAddGroup','params'=>'sub_type,group_id,user_id,comment,flag'),
            )
        );
        $event = $events[$getData['post_type']][$getData[$getData['post_type'].'_type']];
        if($event){
            $params = array();
            foreach(explode(',',$event['params']) as $key){
                $params[] = $getData[$key];
            }
            call_user_func_array($event['func'],$params);
            unset($params);
        }
        unset($event);
        unset($events);
        unset($getData);
    }
    static function sendBefore($method){
        global $cq_config;
        $url = 'http'.($cq_config['ssl']?'s':'').'://'.$cq_config['url'];
        $url .= $cq_config['port'] == 80 ? '' : ':'.$cq_config['port'];
        $url .= '/'.$method.($cq_config['async'] && !in_array($method,array('get_stranger_info'))?'_async':'');
        return $url;
    }

    static function send($method,$data=array()){
        global $cq_config;
        $url = self::sendBefore($method);
        if($cq_config['token']){
            $data['access_token'] = $cq_config['token'];
        }
        $headers = array(
            "Content-type: application/json;charset='utf-8'",
            "Accept: application/json",
            "Cache-Control: no-cache",
            "Pragma: no-cache"
        );
        if($cq_config['async']){
            $result = HTTP::getHttpData($url,$data,$headers);
            $return = self::sendAfter($result);
            return $return;
        }
        for($i=0;$i<$cq_config['try'];$i++){
            $result = HTTP::getHttpData($url,$data,$headers);
            $return = self::sendAfter($result);
            if($return){
                if($cq_config['log']){
                    dlog($data,$return);
                }
                break;
            }
        }
        return $return;
    }

    static function sendAfter($result){
        global $cq_config;
        $data = json_decode($result,true);
        
        if($data['status'] === 'ok'){
            return $data;
        }else{
            return false;
        }
    }
    
    /**
     * 发送私信消息
     * @auth Todd
     * @param number $qq 对方 QQ 号
     * @param string $message 要发送的内容
     * @return array 发送结果
     */
    static function sendPrivateMsg($qq,$message){
        $data = array('user_id'=>$qq,'message'=>$message,'auto_escape'=>1);
        return self::send('send_private_msg',$data);
    }

    /**
     * 发送群消息
     * @auth Todd
     * @param number $group_id 群号
     * @param string $message 要发送的内容
     * @return array 发送结果
     */
    static function sendGroupMsg($group_id,$message){
        $data = array('group_id'=>$group_id,'message'=>$message,'auto_escape'=>1);
        return self::send('send_group_msg',$data);
    }

    /**
     * 发送讨论组消息
     * @auth Todd
     * @param number $discuss_id 讨论组 ID（正常情况下看不到，需要从讨论组消息上报的数据中获得）
     * @param string $message 要发送的内容
     * @return array 发送结果
     */
    static function sendDiscussMsg($discuss_id,$message){
        $data = array('discuss_id'=>$discuss_id,'message'=>$message,'auto_escape'=>1);
        return self::send('send_discuss_msg',$data);
    }

    /**
     * 通用发送消息
     * @auth Todd
     * @param string $message_type 消息类型，支持 private、group、discuss，分别对应私聊、群组、讨论组
     * @param number $num_id 根据消息类型，填写QQ号、群号、讨论组 ID
     * @param string $message 要发送的内容
     * @return array 发送结果
     */
    static function sendMsg($message_type,$num_id,$message){
        $idtype = $message_type == 'private' ? 'user' : $message_type;
        $data = array('message_type'=>$message_type,$idtype.'_id'=>$num_id,'message'=>$message,'auto_escape'=>1);
        return self::send('send_msg',$data);
    }

    /**
     * 撤回消息
     * @auth Todd
     * @param number $message_id 消息 ID
     * @return array 撤回消息结果
     */
    static function deleteMsg($message_id){
        $data = array('message_id'=>$message_id);
        return self::send('delete_msg',$data);
    }
    
    /**
     * 发送好友赞
     * @auth Todd
     * @param number $message_id 消息 ID
     * @return array 发送好友赞结果
     */
    static function sendLike($user_id,$times = 1){
        $data = array('user_id'=>$user_id,'times'=>$times);
        return self::send('send_like',$data);
    }
    
    /**
     * 群组踢人
     * @auth Todd
     * @param number $group_id 群号
     * @param number $user_id 要踢的 QQ 号
     * @param boolean $reject_add_request 拒绝此人的加群请求
     * @return array 群组踢人结果
     */
    static function setGroupKick($group_id,$user_id,$reject_add_request = false){
        $data = array('group_id'=>$group_id,'user_id'=>$user_id,'reject_add_request'=>$reject_add_request);
        return self::send('set_group_kick',$data);
    }

    /**
     * 群组单人禁言或解除禁言
     * @auth Todd
     * @param number $group_id 群号
     * @param number $user_id 要踢的 QQ 号
     * @param number $duration 禁言时长，单位秒，0 表示取消禁言
     * @return array 群组单人禁言处理结果
     */
    static function setGroupBan($group_id,$user_id,$duration = 300){
        $data = array('group_id'=>$group_id,'user_id'=>$user_id,'duration'=>$duration);
        return self::send('set_group_ban',$data);
    }
    

    /**
     * 群组匿名用户禁言
     * @auth Todd
     * @param number $group_id 群号
     * @param object $anonymous 可选，要禁言的匿名用户对象（群消息上报的 anonymous 字段）
     * @param string $anonymous_flag 可选，要禁言的匿名用户的 flag（需从群消息上报的数据中获得）
     * @param number $duration 禁言时长，单位秒，无法取消匿名用户禁言
     * @return array 群组匿名用户禁言结果
     * 上面的 anonymous 和 anonymous_flag 两者任选其一传入即可，若都传入，则使用 anonymous。
     */
    static function setGroupAnonymousBan($group_id,$anonymous,$anonymous_flag,$duration = 300){
        $data = array('group_id'=>$group_id,'anonymous'=>$anonymous,'anonymous_flag'=>$anonymous_flag,'duration'=>$duration);
        return self::send('set_group_anonymous_ban',$data);
    }

    /**
     * 群组全员禁言
     * @auth Todd
     * @param number $group_id 群号
     * @param boolean $enable 是否禁言
     * @return array 群组全员禁言结果
     */
    static function setGroupWholeBan($group_id,$enable = true){
        $data = array('group_id'=>$group_id,'enable'=>$enable);
        return self::send('set_group_whole_ban',$data);
    }
    
    /**
     * 群组设置管理员
     * @auth Todd
     * @param number $group_id 群号
     * @param number $user_id 要设置管理员的 QQ 号
     * @param boolean $enable true 为设置，false 为取消
     * @return array 群组设置管理员结果
     */
    static function setGroupAdmin($group_id,$user_id,$enable = true){
        $data = array('group_id'=>$group_id,'user_id'=>$user_id,'enable'=>$enable);
        return self::send('set_group_admin',$data);
    }
    
    /**
     * 群组匿名
     * @auth Todd
     * @param number $group_id 群号
     * @param boolean $enable 是否允许匿名聊天
     * @return array 群组匿名结果
     */
    static function setGroupAnonymous($group_id,$enable = true){
        $data = array('group_id'=>$group_id,'enable'=>$enable);
        return self::send('set_group_anonymous',$data);
    }
    
    /**
     * 设置群名片（群备注）
     * @auth Todd
     * @param number $group_id 群号
     * @param number $user_id 要设置的 QQ 号
     * @param string $card 群名片内容，不填或空字符串表示删除群名片
     * @return array 设置群名片结果
     */
    static function setGroupCard($group_id,$user_id,$card = ''){
        $data = array('group_id'=>$group_id,'user_id'=>$user_id,'card'=>$card);
        return self::send('set_group_card',$data);
    }
    
    /**
     * 退出群组
     * @auth Todd
     * @param number $group_id 群号
     * @param boolean $is_dismiss 是否解散，如果登录号是群主，则仅在此项为 true 时能够解散
     * @return array 退出结果
     */
    static function setGroupLeave($group_id,$is_dismiss = false){
        $data = array('group_id'=>$group_id,'is_dismiss'=>$is_dismiss);
        return self::send('set_group_leave',$data);
    }
    
    /**
     * 设置群组专属头衔
     * @auth Todd
     * @param number $group_id 群号
     * @param number $user_id 要设置的 QQ 号
     * @param string $special_title 专属头衔，不填或空字符串表示删除专属头衔
     * @param number $duration 专属头衔有效期，单位秒，-1 表示永久，不过此项似乎没有效果，可能是只有某些特殊的时间长度有效，有待测试
     * @return array 设置结果
     */
    static function setGroupSpecialTitle($group_id,$user_id,$special_title='', $duration= -1){
        $data = array('group_id'=>$group_id,'user_id'=>$user_id,'special_title'=>$special_title,'duration'=>$duration);
        return self::send('set_group_special_title',$data);
    }
    
    /**
     * 退出讨论组
     * @auth Todd
     * @param number $discuss_id 讨论组 ID（正常情况下看不到，需要从讨论组消息上报的数据中获得）
     * @return array 退出结果
     */
    static function setDiscussLeave($discuss_id){
        $data = array('group_id'=>$discuss_id);
        return self::send('set_discuss_leave',$data);
    }

    /**
     * 处理加好友请求
     * @auth Todd
     * @param string $flag 加好友请求的 flag（需从上报的数据中获得）
     * @param boolean $approve 是否同意请求
     * @param string $remark 添加后的好友备注（仅在同意时有效）
     * @return array 处理结果
     */
    static function setFriendAddRequest($flag,$approve=true,$remark=''){
        $data = array('flag'=>$flag,'approve'=>$approve,'remark'=>$remark);
        return self::send('set_friend_add_request',$data);
    }

    /**
     * 处理加群请求／邀请
     * @auth Todd
     * @param string $flag 加好友请求的 flag（需从上报的数据中获得）
     * @param string $sub_type add 或 invite，请求类型（需要和上报消息中的 sub_type 字段相符）
     * @param boolean $approve 是否同意请求／邀请
     * @param string $reason 拒绝理由（仅在拒绝时有效）
     * @return array 处理结果
     */
    static function setGroupAddRequest($flag,$sub_type='add',$approve=true,$reason=''){
        $data = array('flag'=>$flag,'sub_type'=>$sub_type,'approve'=>$approve,'reason'=>$reason);
        return self::send('set_group_add_request',$data);
    }

    /**
     * 获取登录号信息
     * @auth Todd
     * @return array 信息
     * @return number user_id QQ 号
     * @return string nickname QQ 昵称
     */
    static function getLoginInfo(){
        return self::send('get_login_info');
    }

    /**
     * 获取陌生人信息
     * @auth Todd
     * @param number $user_id QQ 号（不可以是登录号）
     * @param boolean $no_cache 是否不使用缓存（使用缓存可能更新不及时，但响应更快）
     * @return array 返回信息
     * @return number user_id QQ 号
     * @return string nickname QQ 昵称
     * @return string sex 性别，male 或 female 或 unknown
     * @return number age 年龄
     */
    static function getStrangerInfo($user_id,$no_cache=false){
        $data = array('user_id'=>$user_id,'no_cache'=>$no_cache);
        return self::send('get_stranger_info',$data);
    }

    /**
     * 获取群列表
     * @auth Todd
     * @return array 信息
     * @return number group_id 群号
     * @return string group_name 群名称
     */
    static function getGroupList(){
        return self::send('get_group_list');
    }

    /**
     * 获取群成员信息
     * @auth Todd
     * @param number $group_id 群号
     * @param number $user_id QQ 号（不可以是登录号）
     * @param boolean $no_cache 是否不使用缓存（使用缓存可能更新不及时，但响应更快）
     * @return array 返回信息
     * @return number group_id 群号 
	 * @return number user_id QQ 号 
	 * @return string nickname 昵称 
	 * @return string card 群名片／备注 
	 * @return string sex 性别，male 或 female 或 unknown 
	 * @return number age 年龄 
	 * @return string area 地区 
	 * @return number join_time 加群时间戳 
	 * @return number last_sent_time 最后发言时间戳 
	 * @return string level 成员等级 
	 * @return string role 角色，owner 或 admin 或 member 
	 * @return boolean unfriendly 是否不良记录成员 
	 * @return string title 专属头衔 
	 * @return number title_expire_time 专属头衔过期时间戳 
	 * @return boolean card_changeable 是否允许修改群名片 
     */
    static function getGroupMemberInfo($group_id,$user_id,$no_cache=false){
        $data = array('group_id'=>$group_id,'user_id'=>$user_id,'no_cache'=>$no_cache);
        return self::send('get_group_member_info',$data);
    }

    
    /**
     * 获取群成员列表
     * @auth Todd
     * @param number $group_id 群号
     * @return array 返回信息
     * @return number group_id 群号 
	 * @return number user_id QQ 号 
	 * @return string nickname 昵称 
	 * @return string card 群名片／备注 
	 * @return string sex 性别，male 或 female 或 unknown 
	 * @return number age 年龄 
	 * @return string area 地区 
	 * @return number join_time 加群时间戳 
	 * @return number last_sent_time 最后发言时间戳 
	 * @return string level 成员等级 
	 * @return string role 角色，owner 或 admin 或 member 
	 * @return boolean unfriendly 是否不良记录成员 
	 * @return string title 专属头衔 
	 * @return number title_expire_time 专属头衔过期时间戳 
	 * @return boolean card_changeable 是否允许修改群名片
     * 响应内容为 JSON 数组，每个元素的内容和上面的 getGroupMemberInfo 接口相同，但对于同一个群组的同一个成员，获取列表时和获取单独的成员信息时，某些字段可能有所不同，例如 area、title 等字段在获取列表时无法获得，具体应以单独的成员信息为准。 
     */
    static function getGroupMemberList($group_id){
        $data = array('group_id'=>$group_id);
        return self::send('get_group_member_list',$data);
    }

    /**
     * 获取 Cookies
     * @auth Todd
     * @return array 信息
	 * @return string cookies Cookies 
     */
    static function getCookies(){
        return self::send('get_cookies');
    }
    /**
     * 获取 CSRF Token
     * @auth Todd
     * @return array 信息
	 * @return number token CSRF Token 
     */
    static function getCsrfToken(){
        return self::send('get_csrf_token');
    }

    
    /**
     * 获取 QQ 相关接口凭证
     * @auth Todd
     * @return array 信息
	 * @return string cookies Cookies 
	 * @return number token CSRF Token 
     */
    static function getCredentials(){
        return self::send('get_credentials');
    }

    /**
     * 获取插件运行状态
     * @auth Todd
     * @return array 信息
	 * @return boolean app_initialized HTTP API 插件已初始化 
	 * @return boolean app_enabled HTTP API 插件已启用 
	 * @return boolean plugins_good HTTP API 的内部插件全部正常运行 
	 * @return boolean app_good HTTP API 插件正常运行（已初始化、已启用、各内部插件正常运行） 
	 * @return boolean online 当前 QQ 在线 
	 * @return boolean good HTTP API 插件状态符合预期，意味着插件已初始化，内部插件都在正常运行，且 QQ 在线 
     * 通常情况下建议只使用 online 和 good 这两个字段来判断运行状态，因为随着插件的更新，其它字段有可能频繁变化。
     */
    static function getStatus(){
        return self::send('get_status');
    }

    /**
     * 获取酷 Q 及 HTTP API 插件的版本信息
     * @auth Todd
     * @return array 信息
	 * @return string coolq_directory 酷 Q 根目录路径 
	 * @return string coolq_edition 酷 Q 版本，air 或 pro 
	 * @return string plugin_version HTTP API 插件版本，例如 2.1.3 
	 * @return number plugin_build_number HTTP API 插件 build 号 
	 * @return string plugin_build_configuration HTTP API 插件编译配置，debug 或 release 
     */
    static function getVersionInfo(){
        return self::send('get_version_info');
    }
    
    /**
     * 重启酷 Q，并以当前登录号自动登录（需勾选快速登录）
     * @auth Todd
     * @param boolean $clean_log 是否在重启时清空酷 Q 的日志数据库（logv1.db）
     * @param boolean $clean_cache 是否在重启时清空酷 Q 的缓存数据库（cache.db）
     * @param boolean $clean_event 是否在重启时清空酷 Q 的事件数据库（eventv2.db）
     * @return array 处理结果
     */
    static function setRestart($clean_log=false,$clean_cache=false,$clean_event=false){
        $data = array('clean_log'=>$clean_log,'clean_cache'=>$clean_cache,'clean_event'=>$clean_event);
        return self::send('set_restart',$data);
    }

    
    /**
     * 重启 HTTP API 插件
     * @auth Todd
     * @param number $delay 要延迟的毫秒数，如果默认情况下无法重启，可以尝试设置延迟为 2000 左右
     * @return array 处理结果
     * 由于重启插件同时需要重启 API 服务，这意味着当前的 API 请求会被中断，因此需在异步地重启插件，接口返回的 status 是 async。
     */
    static function setRestartPlugin($delay=0){
        $data = array('delay'=>$delay);
        return self::send('set_restart_plugin',$data);
    }
    
    /**
     * 清理数据目录
     * @auth Todd
     * @param string $data_dir 收到清理的目录名，支持 image、record、show、bface
     * @return array 处理结果
     * 用于清理积攒了太多旧文件的数据目录，如 image。
     */
    static function cleanDataDir($data_dir){
        $data = array('data_dir'=>$data_dir);
        return self::send('clean_data_dir',$data);
    }
    
    /**
     * 清理数据目录
     * @auth Todd
     * @return array 处理结果
     * 用于清空插件的日志文件。
     */
    static function cleanPluginLog(){
        return self::send('clean_plugin_log');
    }

    /* 
     * 以下为试验性 API 列表
     * 试验性 API 可以一定程度上增强实用性，但它们并非酷 Q 原生提供的接口，不保证随时可用（如果不可用可以尝试重新登录酷 Q），且接口可能会在后面的版本中发生变动。
     * 所有试验性接口都以下划线（_）开头。
    */



    /**
     * 获取好友列表
     * @auth Todd
     * @param boolean $flat 是否获取扁平化的好友数据，即所有好友放在一起、所有分组放在一起，而不是按分组层级
     * @return array 获取结果
     */
    static function getFriendList($flat){
        $data = array('flat'=>$flat);
        return self::send('_get_friend_list');
    }

    /**
     * 获取群信息
     * @auth Todd
     * @param number $group_id 要查询的群号
     * @return array 获取结果
     */
    static function getGroupInfo($group_id){
        $data = array('group_id'=>$group_id);
        return self::send('_get_group_info');
    }

    /**
     * 获取群信息
     * @auth Todd
     * @param number $user_id 要查询的 QQ 号
     * @return array 获取结果
     */
    static function getVipInfo($user_id){
        $data = array('user_id'=>$user_id);
        return self::send('_get_vip_info');
    }

}