<?php

echo "------------------------\033[5m\033[1m\033[47;30m 12345六 SWOOLE CHAT \033[0m-------------------------------\n\n";

class   swooleChat
{
	// 版本
	const	VERSION		= '1.0';
	// 服务名
	const	NAME		= 'SWOOLE CHAT';
	// 作者
	const	WRITER		= '12345六';
	
	// 主进程pid文件
	const	PID_FILE	= './pid';
	// log文件
	const	LOG_DIR		= './mylog';
		
		
	// ip
	public	static	$ip;
	// port
	public	static	$port;
	// 主进程pid
	public	static	$masterPid;
	// webSocket 实例
	public	static	$ws;
	// 接收的所有链接
	public	static	$connections	= array();
	
	
	public	function	__construct($ip, $port)
	{
		self::$ip	= $ip;
		self::$port	= $port;
                
		//输出信息
		self::notice("\t\033[34m 12345六 SWOOLE CHAT is starting ... \033[0m\n");
		// 检查环境
		self::checkEnv();
		// 保存进程id
		self::savePid();
		// start
		self::run();
	}
		
        /**
         * start
         * @return void
         */
	public	static	function	run()
	{
		// 设置进程名称，如果支持的话
		self::setProcTitle(self::WRITER.'---'.self::NAME.'---'.self::VERSION.'---'.date('Y-m-d H:i:s'));
		
		self::$ws	= new swoole_websocket_server(self::$ip, self::$port);

		// 设置配置
		self::$ws->set(
			array(
				'daemonize'			=> true,	// 是否是守护进程
				'max_request'			=> 10000,	// 最大连接数量
				'dispatch_mode'			=> 2,		// FD取模固定分配数据包
				'debug_mode'			=> 1,		// debug模式
				'heartbeat_check_interval'	=> 5,		// 心跳健康检测
				'heartbeat_idle_time'		=> 600,		// 连接空闲时间
	        	)
		);
		
		// 监听WebSocket连接打开事件
		self::$ws->on('open', function($ws, $request) 
		{
		        $msg	= 'ip：'.$request->server['remote_addr'].'已经入房间！';
		        foreach (self::$connections as $fd => $ip)
		        {
		                self::$ws->push($fd, json_encode(array('type'=>'system', 'name'=>'12345六 SWOOLE CHAT', 'message'=>$msg)));
		        }
		        
		        self::$connections[$request->fd]	= $request->server['remote_addr'];
		        self::$ws->push($request->fd, json_encode(array('type'=>'system', 'name'=>'12345六 SWOOLE CHAT', 'message'=>'hello, welcome to 12345六 SWOOLE CHAT')));
		});
		
		// 监听WebSocket消息事件
		self::$ws->on('message', function ($ws, $frame) 
		{
		        $data	= json_decode($frame->data, true);
		        
		        $data['type']	= 'usermsg';
		        		        
		        foreach (self::$connections as $fd => $ip)
		        {
		                self::$ws->push($fd, json_encode($data));
		        }
		        
		        
		});
		
		// 监听WebSocket连接关闭事件
		self::$ws->on('close', function ($ws, $fd) 
		{
		        if (array_key_exists($fd, self::$connections))
		        {
		                $msg    = 'ip：'.self::$connections[$fd].'已离开房间！';
		                unset(self::$connections[$fd]);
		                foreach (self::$connections as $fd => $ip)
		                {
		                        self::$ws->push($fd, json_encode(array('type'=>'system', 'name'=>'12345六 SWOOLE CHAT', 'message'=>$msg)));
		                }
		        }
		});

		self::notice("\t\033[1m\033[31m 12345六 SWOOLE CHAT start success ... \033[0m\n");
		
		// 启动服务器
		self::$ws->start();
		
        }
		
	/**
         * 检查环境配置
         * @return void
         */
        public  static  function        checkEnv()
        {
                // 检查进程是否存在
                self::checkProcess();
                // 检查log目录是否可读
                self::checkLogWriteable();
                // 设置时区
                self::setDefaultTimeZone();
                
                self::notice("\t\033[32m check environment success ... \033[0m\n");
        }
		
	/**
         * 保存主进程pid
         * @return void
         */
        public  static  function        savePid()
        {
                // 保存在变量中
                self::$masterPid        = posix_getpid();
                
                // 保存到文件中，用于实现停止、重启
                if(false === @file_put_contents(self::PID_FILE, (self::$masterPid)))
                {
                        exit("\033[31;40mCan not save pid to pid-file(".self::PID_FILE.")\033[0m\n\n\033[31;40mServer start fail\033[0m\n\n");
                }
                
                self::notice("\t\033[33m pid write success ... \033[0m\n");
                // 更改权限
                chmod(self::PID_FILE, 0666);
        }


		
	//记录日志
	public	static	function	notice($msg)
	{
		echo $msg, "\n";
	}
		
	/**
         * 设置进程名称，需要proctitle支持 或者php>=5.5
         * @param string $title
         * @return void
         */
        public  static  function        setProcTitle($title)
        {
                // >=php 5.5
                if (function_exists('cli_set_process_title'))
                {
                        @cli_set_process_title($title);
                }
                // 需要扩展
                elseif(extension_loaded('proctitle') && function_exists('setproctitle'))
                {
                        @setproctitle($title);
                }
        }
		
	/**
         * 检查log目录是否可写
         * @return bool
         */
        public  static  function        checkLogWriteable()
        {
                $ok     = true;
                if(!is_dir(self::LOG_DIR))
                {
                        // 检查log目录是否可读
                        umask(0);
                        if(@touch(self::LOG_DIR, 0777) === false)
                        {
                                $ok     = false;
                        }
                        @chmod(self::LOG_DIR, 0777);
                }

                if (!is_readable(self::LOG_DIR) || !is_writeable(self::LOG_DIR))
                {
                        $ok     = false;
                }

                if(!$ok)
                {
                        $pad_length = 26;
                        self::notice(self::LOG_DIR." Need to have read and write permissions\t 12345六 SWOOLE CHAT start fail");
                }

                return;
        }
        
        /**
         * 设置时区
         * @return bool
         */
        public	static	function	setDefaultTimeZone()
        {
                date_default_timezone_set('PRC');
                
                return true;
        }
        
        /**
         * 设置时区
         * @return bool
         */
	public	static	function	writeLog($msg)
	{
	        $cont   = var_export($msg, true)."\n";
	        $time   = date('Y年m月d日 H点i分s秒：');
	        
	        @file_put_contents(self::LOG_DIR,  $time, FILE_APPEND);
	        @file_put_contents(self::LOG_DIR,  $cont, FILE_APPEND);
	}
	
	public	static	function	checkProcess()
	{
	        if (system("ps -ef | grep ".self::WRITER." | grep -v grep"))
                {
                        self::notice("\t \033[5m\033[1m\033[47;31m12345六 SWOOLE CHAT is started ... \033[0m\n");
                        exit;
                }
	}
		
	/********************************************************************************************************/
	//类私有方法
		

} 

new swooleChat('0.0.0.0', 10000);

