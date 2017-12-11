<table class="oTable" border="">
    <tr style="background-color: #EEEEEE;">
        <td>字段</td>
        <td>变更前</td>
        <td>变更后</td>
    </tr>
    <tbody  >

    <?php
    if( !empty($pre_data) ) {

        foreach ($pre_data as $k=>$pre){

            $after = '--';
            if( isset($cur_data[$k]) ) {
                $after = $cur_data[$k];
            }
            if( $pre!=$after ){
                $pre = '<span style="color:green">'.$pre.'</span>';
                $after = '<span style="color: red">'.$after.'</span>';
            }

      ?>
            <tr>
                <td><?=$k?></td>
                <td><?=strval($pre)?></td>
                <td><?=strval($after)?></td>
            </tr>
     <?php
        }
    }else{
        echo '<tr>
                <td>--</td>
                <td>--</td>
                <td>--</td>
            </tr>';
    }

    ?>
    </tbody>
</table>