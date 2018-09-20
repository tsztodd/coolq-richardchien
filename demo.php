<?php
require_once './config_coolq.php';
require_once './coolq-richardchien.class.php';
CQ::init();

/* 调用方法,以发送私聊消息为例
 * CQ::sendPrivateMsg('12345','hello Word!');
 * 即可
*/

/**
 * 日志功能
 * @param array $data 请求的数据
 * @param array $return 请求结果
 */
function dlog($data,$return){
    
}

/**
 * 收到私聊消息
 * @param string $sub_type 消息子类型，如果是好友则是 friend，如果从群或讨论组来的临时会话则分别是 group、discuss
 * @param number $message_id 消息ID
 * @param number $qq 来源QQ
 * @param message $message 消息内容
 */
function _event_PrivateMsg($sub_type,$message_id,$qq,$message){
   
}

/**
 * 收到群消息
 * @param string $sub_type 消息子类型，正常消息是 normal，匿名消息是 anonymous，系统提示（如「管理员已禁止群内匿名聊天」）是 notice
 * @param number $message_id 消息ID
 * @param number $group_id 群号
 * @param number $user_id 发送者 QQ 号
 * @param object $anonymous 匿名信息，如果不是匿名消息则为 null
 * @param message $message 消息内容
 */
function _event_GroupMsg($sub_type,$message_id,$group_id,$user_id,$anonymous,$message){
    global $_G;

}

/**
 * 收到讨论组消息
 * @param number $message_id 消息ID
 * @param number $discuss_id 讨论组 ID
 * @param number $user_id 发送者 QQ 号
 * @param message $message 消息内容
 */
function _event_DiscussMsg($message_id,$discuss_id,$user_id,$message){
    global $_G;

}

/**
 * 群文件上传事件
 * @param number $group_id 群号
 * @param number $user_id 发送者 QQ 号
 * @param object $file 文件信息
 */
function _event_GroupFileUpload($group_id,$user_id,$file){
    global $_G;

}

/**
 * 管理员变动事件
 * @param string $sub_type 事件子类型，set设置和unset取消管理员
 * @param number $group_id 群号
 * @param number $user_id 管理员 QQ 号
 */
function _event_GroupAdminChange($sub_type, $group_id, $user_id){
    global $_G;

}

/**
 * 群成员减少事件
 * @param string $sub_type 事件子类型，leave主动退群、kick成员被踢、kick_me登录号被踢
 * @param number $group_id 群号
 * @param number $operator_id 操作者 QQ 号（如果是主动退群，则和 user_id 相同）
 * @param number $user_id 离开者 QQ 号
 */
function _event_GroupMemberDecrease($sub_type, $group_id, $operator_id, $user_id){
    global $_G;

}

/**
 * 群成员增加事件
 * @param string $sub_type 事件子类型，approve管理员已同意入群、invite管理员邀请入群
 * @param number $group_id 群号
 * @param number $operator_id 操作者 QQ 号
 * @param number $user_id 加入者 QQ 号
 */
function _event_GroupMemberIncrease($sub_type, $group_id, $operator_id, $user_id){
    global $_G;

}

/**
 * 好友添加事件
 * @event 201
 * @param number $user_id 新添加好友 QQ 号
 */
function _event_FriendIsAdd($user_id){
    global $_G;

}

/**
 * 加好友请求事件
 * @event 301
 * @param number $user_id 发送请求的 QQ 号
 * @param string $comment 验证信息
 * @param string $flag 请求 flag，在调用处理请求的 API 时需要传入
 */
function _event_RequestAddFriend($user_id, $comment, $flag){
    global $_G;

}

/**
 * 加群请求／邀请 事件
 * @param string $sub_type 事件子类型，add加群请求、invite邀请登录号入群
 * @param number $group_id 群号
 * @param number $user_id 发送请求的 QQ 号
 * @param string $comment 验证信息
 * @param string $flag 请求 flag，在调用处理请求的 API 时需要传入
 */
function _event_RequestAddGroup($sub_type,$group_id,$user_id, $comment, $flag){
    global $_G;

}