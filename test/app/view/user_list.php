<?php
//debug_print_backtrace() ;
//print_r( array_reverse( debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS) ) );
use main\app\classes\UserLogic;
?>

<html>
<head>
    <meta charset="UTF-8">
</head>
<body> 
    <table width="800" border="1">
        <caption>
            用户列表
        </caption>
        <tr>
            <th scope="col">用户名</th>
            <th scope="col">手机号</th>
            <th scope="col">Email</th>
            <th scope="col">注册时间</th>
        </tr>
        <?php
        
        foreach( $users as $u ){

            $reg_time = !empty($u['reg_time']) ? date('Y-m-d H:i',$u['reg_time'] ):'';
        ?>
        <tr>
            <td><?=$u['name']?></td>
            <td><?=$u['phone']?></td>
            <td><?=$u['email']?></td>
            <td><?=$reg_time?></td>
        </tr> 
        <?php
        } 
        ?>
       
    </table> 
</body>
</html>