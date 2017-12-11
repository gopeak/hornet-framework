<?php
use main\app\classes\Common;

?>
<?php include VIEW_PATH . 'common/header.php'; ?>


				<div class="conten-dir">
					<span><a href="<?=$site_url?>">首页</a></span>
					<span>/操作日志</span>
				</div>
				<div class="conten-search">
					<label for="right-search-text">
					描述<input type="text" id="search_remark"/>
				</label>
					<label for="right-search-user">
					用户<input type="text" id="search_user_name"/>
				</label>

				 <label for="">操作类型
                    <select name="" id="search_action">
                        <option value="">请选择</option>
                        <?php
                            foreach( $actions as $c ){
                                echo '<option value="'.$c.'">'.$c.'</option>';
                            }
                        ?>
                    </select>
				</label>
					<!--<input type="button" class="right-search-btn" value="搜索"/>-->
					<span class="search-btn" onclick="search();">搜索</span>
				</div>

				<div class="conten-table">
					<table class="oTable" border="">
						<tr style="background-color: #EEEEEE;">
							<td>序号</td>
							<td>用户</td>
                            <td>用户名称</td>
							<td>模块</td>
							<td>页面</td>
							<td>操作类型</td>
							<td>时间</td>
							<td>详情描述</td>
                            <td>变更细节</td>
						</tr>
                        <tbody id="data_id">

                        </tbody>
					</table>
				</div>

				<div class="footer">
					<div class="page" id="ajax_page_id">
					</div>
				</div>

<script type="text/html"  id="log_tpl">
    {{#logs}}
    <tr>
        <td>{{i}}</td>
        <td>{{user_name}}</td>
        <td>{{real_name}}</td>
        <td>{{module}}</td>
        <td>{{page}}</td>
        <td>{{action}}</td>
        <td>{{time_str}}</td>
        <td>{{remark}}</td>
        <td><a href="javascript:show_diff({{id}})" >细节</a></td>
    </tr>
    {{/logs}}

</script>


<script src="<?=PUBLIC_URL?>js/jquery.min.js" type="text/javascript" charset="utf-8"></script>
<script src="<?=PUBLIC_URL?>js/handlebars-v4.0.10.js" type="text/javascript" charset="utf-8"></script>
<script src="<?=PUBLIC_URL?>js/layer/layer.js" type="text/javascript" charset="utf-8"></script>

<?php include VIEW_PATH . 'common/com-js.php'; ?>



<script type="text/javascript">

    var fetch_log_url = "/log/_list";
    $(document).ready(function () {

        getAjaxPage( fetch_log_url, 1,  "data_id", "ajax_page_id");
    });

    function search(){
        getAjaxPage( fetch_log_url, 1, "data_id", "ajax_page_id");
    }

    function getAjaxPage( new_url, page, div_id, page_id ) {
        var fnName = arguments.callee;
        var params = {  page:page, format:'json' ,remark:$('#search_remark').val(),user_name:$('#search_user_name').val(),action:$('#search_action').val()};
        $.ajax({
            type: "GET",
            dataType: "json",
            async: true,
            url: new_url,
            data: params ,
            success: function (res) {

                var source = $('#log_tpl').html();
                var template = Handlebars.compile(source);
                var result = template(res.data);

                $('#' + div_id).html(result);
                $('#' + page_id).html(res.data.page_str);
                $('#' + page_id + " a").each(function () {
                    $(this).click(function () {
                        fnName(new_url, $(this).attr('page'), div_id, page_id);
                    });
                });
                $('#' + page_id + " input[type='button']").click(function () {
                    fnName(new_url, $('#' + page_id +" input[type='text']").val(), div_id, page_id);
                });
            },
            error: function (res) {
                alert("请求数据错误" + res);
            }
        });
    }

    function show_diff( id ){

        $.ajax({
            type: "GET",
            async: true,
            url: '/log/detail',
            data: { id:id },
            success: function (res) {
                layer.alert( res );
            },
            error: function (res) {
                alert("请求数据错误" + res);
            }
        });
    }

</script>

<?php include APP_PATH . 'view/common/footer.php'; ?>
