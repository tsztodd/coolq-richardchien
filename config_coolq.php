<?php
$cq_config = array();
$cq_config['mode'] = 'richardchien';//酷Q开发者名称,小写
$cq_config['ssl'] = false;//是否是https
$cq_config['url'] = '127.0.0.1';//域名或IP
$cq_config['port'] = '5700';//端口
$cq_config['token'] = '';//如果设置，则请求将会加入TOKEN进行校验
$cq_config['try'] = 5;//最大尝试发送次数
$cq_config['log'] = true;//是否记录LOG，自定义log方法
$cq_config['async'] = false;//是否异步执行