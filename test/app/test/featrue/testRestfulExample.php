<?php
/**
 * Created by PhpStorm.
 * User: sven
 * Date: 2017/7/26 0026
 * Time: 下午 5:16
 */



/**
 *
 * 测试 Restfule example
 * @version php v7.1.1
 * @author     2017-3-28 Jesen
 * @copyright  2014-2017 闪盟珠宝
 * @link
 */


class testRestfulExample extends BaseTestCase
{

    /**
     *
     * @var \Curl\Curl
     */
    public static  $curl = NULL;




    /**
     * 测试 获取所有用户
     */
    public function testGetUsers()
    {
        $curl = new \Curl\Curl();
        $curl->get( ROOT_URL.'api/restfulExample/users' );
        $this->assertEquals( 200, $curl->httpStatusCode );
        $json = json_decode(  $curl->rawResponse, true );
        if( empty( $json ) ){
            $this->fail( 'GET '.ROOT_URL.'api/restfulExample/users'.'  is not json data:'. $curl->rawResponse );
        }
        $this->assertEquals( '200', $json['ret'] );
        $this->assertNotEmpty( $json['data'],$curl->rawResponse );
        $this->assertTrue( count($json['data'])>1  );
        $curl->close();
    }

    /**
     * 测试 获取单个用户
     */
    public function testGetUser()
    {
        $curl = new \Curl\Curl();
        $curl->get( ROOT_URL.'api/restfulExample/users/1' );
        $this->assertEquals( 200, $curl->httpStatusCode );
        $json = json_decode(  $curl->rawResponse, true );
        if( empty( $json ) ){
            $this->fail( 'GET '.ROOT_URL.'api/restfulExample/users/1'.'  is not json data:'. $curl->rawResponse );
        }
        $this->assertEquals( '200', $json['ret'] );
        $this->assertNotEmpty( $json['data'],$curl->rawResponse );
        $this->assertTrue( isset($json['data']['name']) );
        $curl->close();
    }

    /**
     * 测试 新增用户
     */
    public function testPostUser()
    {
        $curl = new \Curl\Curl();
        $post_data = [];
        $post_data['name'] = 'SAT用户';
        $post_data['count'] = 23;
        $curl->post( ROOT_URL.'api/restfulExample/users',$post_data );
        $this->assertEquals( 200, $curl->httpStatusCode );
        $json = json_decode(  $curl->rawResponse, true );
        if( empty( $json ) ){
            $this->fail( 'POST '.ROOT_URL.'api/restfulExample/users'.'  is not json data:'. $curl->rawResponse );
        }
        $this->assertEquals( '200', $json['ret'] );
        $this->assertNotEmpty( $json['data'],$curl->rawResponse );
        $this->assertTrue( count($json['data'])>2  );
        $curl->close();
    }

    /**
     * 测试 更新用户全部信息
     */
    public function testPutUser()
    {
        $curl = new \Curl\Curl();
        $post_data = [];
        $post_data['name'] = 'SAT-'.time();
        $post_data['count'] = 25;

        $curl->put( ROOT_URL.'api/restfulExample/users/1', $post_data );
        $this->assertEquals( 200, $curl->httpStatusCode );
        $resp = $curl->rawResponse;
        $json = json_decode(  $resp, true );
        if( empty( $json ) ){
            $this->fail( 'PUT '.ROOT_URL.'api/restfulExample/users/1'.'  is not json data:'. $resp );
        }
        $this->assertEquals( '200', $json['ret'] );
        $this->assertNotEmpty( $json['data'],$resp );
        $this->assertEquals( $post_data['name'], $json['data']['1']['name']);
        $this->assertEquals( $post_data['count'], $json['data']['1']['count']);
        $curl->close();

    }

    /**
     * 测试 更新部分用户信息
     */
    public function testPatchUser()
    {
        $curl = new \Curl\Curl();
        $post_data = [];
        $post_data['name'] = 'SAT-'.strval(time()+100 );
        $curl->patch( ROOT_URL.'api/restfulExample/users/1', $post_data );
        $this->assertEquals( 200, $curl->httpStatusCode );
        $json = json_decode(  $curl->rawResponse, true );
        if( empty( $json ) ){
            $this->fail( 'PATCH '.ROOT_URL.'api/restfulExample/users/1'.'  is not json data:'. $curl->rawResponse );
        }
        $this->assertEquals( '200', $json['ret'] );
        $this->assertNotEmpty( $json['data'], $curl->rawResponse );
        $this->assertEquals( $post_data['name'], $json['data']['1']['name'] , $curl->rawResponse  );
        $curl->close();
    }

    /**
     * 测试 删除某个用户
     */
    public function testDeleteUser()
    {
        $curl = new \Curl\Curl();
        $curl->delete( ROOT_URL.'api/restfulExample/users/1' );
        $this->assertEquals( 200, $curl->httpStatusCode );
        $json = json_decode(  $curl->rawResponse, true );
        if( empty( $json ) ){
            $this->fail( 'DELETE '.ROOT_URL.'api/restfulExample/users/1'.'  is not json data:'. $curl->rawResponse );
        }
        $this->assertEquals( '200', $json['ret'] );
        $this->assertNotEmpty( $json['data'],$curl->rawResponse );
        $this->assertEquals( 1,count($json['data']) );
        $curl->close();
    }


}
