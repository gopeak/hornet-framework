<?php

namespace main\app\model;



/**
 *  
 * User model
 * @author Sven
 */
class UserModel extends DbModel
{
    public $prefix = 'xphp_';

    public $table = 'user';

    public $fields = ' * ';

    public $primary_key = 'id';


    const  DATA_KEY = 'user/';

    const  REG_RETURN_CODE_OK    = 1;
    const  REG_RETURN_CODE_EXIST = 2;
    const  REG_RETURN_CODE_ERROR = 3;

    /**
     * 登录成功
     */
    const  LOGIN_CODE_OK    = 1;

    /**
     * 已经登录过了
     */
    const  LOGIN_CODE_EXIST = 2;


    /**
     * 登录失败
     */
    const  LOGIN_CODE_ERROR = 3;

    /**
     * 登录需要验证码
     */
    const  LOGIN_CODE_NEEDVCODE =4;

    /**
     * 验证码错误
     */
    const  LOGIN_CODE_VCODE_ERR =5;

    const  STATUS_NORMAL = 1;
    const  STATUS_DISABLED = 2;
    const  STATUS_DELETED = 3;

    static public $status   = [
        self::STATUS_NORMAL=>'正常',
        self::STATUS_DELETED=>'删除',
        self::STATUS_DISABLED=>'冻结'
    ];


    public $uid = '';
    public $master_id = null;
    public $is_master = null;


    /**
     * 用于实现单例模式
     * @var self
     */
    protected static $_instance;



    /**
     * 创建一个自身的单例对象
     * @param array $dbConfig
     * @param bool $PERSISTENT
     * @throws PDOException
     * @return self
     */
    public static function getInstance( $uid ='',$persisten=false )
    {
        $index = $uid.strval( intval($persisten) );
        if( !isset(self::$_instance[$index] ) || !is_object( self::$_instance[$index]) ) {

            self::$_instance[$index]  = new self( $uid,$persisten );
        }
        return self::$_instance[$index] ;
    }

    function __construct( $uid ='',$PERSISTENT=false )
    {

        parent::__construct( $uid,$PERSISTENT );

        $this->uid = $uid;

    }
 

    /**
     * 取得一个用户的基本信息
     * @return array
     */
    public function getUser( )
    {
        $uid    = $this->uid;
        $fileds	= '*';
        $where  = array('uid'=>$uid);
        $finally = $this->getRow($fileds,$where);
        return  $finally;
    }

    /**
     * @param $uid
     * @return array
     */
    public function getUserByUid($master_uid){
        $fileds	= '*';
        $where  = array('uid'=>$master_uid);
        $finally = $this->getRow($fileds,$where);
        return  $finally;
    }



    public function getUserByOpenid( $openid )
    {

        $table	= $this->getTable();
        $fileds	=	"*,{$this->primary_key} as k";
        //$where	=	" Where `openid`='$openid'   ";
        $where = ['openid' => trim($openid)];
        $user	=	$this->getRow($fileds, $where );
        return  $user;
    }
 


    /**
     * 添加用户
     * @param $userinfo   提交的用户信息
     * @return bool
     */
    public function addUser( $userinfo )
    {
        if(empty($userinfo))
        {
            return array( self::REG_RETURN_CODE_ERROR,array() );
        }
        $flag = $this->insert( $userinfo);

        if($flag)
        {
            $uid = $this->lastInsertId();
            $this->uid = $uid;
            $user = $this->getUser(true);
            return  array( self::REG_RETURN_CODE_OK, $user );
        }else{
            return  array( self::REG_RETURN_CODE_ERROR, [] );
        }


    }

  

    /**
     * 获取当前账号的主账号，如果是自己返回自己的uid
     * @return string $master_id
     */
    public function getMasterUid()
    {
        $auth =  UserAuth::getInstance();
        return $auth->getMasterUid();
    }
    
    /**
     * 获取当前登陆用户的主账号信息，自己是主账号返回自己的信息
     */
    public function getMasterInfo()
    {
        $auth =  UserAuth::getInstance();
        $master_info = $auth->getMasterInfo();
        return $master_info;
    }
    
   

    /**
     * 根据用户名获取用户
     * @param $username
     * @return  一条查询数据
     */
    public function getUserByUsername($username)
    {
        $table = $this->getTable();
        $fields = '*';

        $conditions = ['username' => $username];

        return parent::getRow( $fields, $conditions);
    }

    /**
     * 根据手机号获取用户
     * @param $mobile
     * @return 一条查询数据
     */
    public function getUserByMobile( $mobile )
    {
        $table = $this->getTable();
        $fields = '*';
        $conditions = ['phone' => $mobile];

        return parent::getRow($fields, $conditions);
    }


 

}
