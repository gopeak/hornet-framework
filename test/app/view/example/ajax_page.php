<html>
<head>
    <meta charset="UTF-8">
    <title>ajax分页demo</title>
    <link rel="stylesheet" type="text/css" href="/dev/css/config.css"/>
</head>
<div class="page-bottom">

    <div class="page" id="ajax_page_id"></div>

</div>

<div class="page" id="data_id"></div>

<script src="/dev/js/jquery.min.js" type="text/javascript" charset="utf-8"></script>

<script type="text/javascript">


    $(document).ready(function () {
        var ajax_data_url =
            getAjaxPage("/framework/ajax_data", 1, "data_id", "ajax_page_id");
    });
    function getAjaxPage( new_url, page, div_id, page_id ) {
        var fnName = arguments.callee;

        $.ajax({
            type: "GET",
            dataType: "json",
            async: true,
            url: new_url,
            data: {page: page},
            success: function (res) {
                $('#' + div_id).html(res.data.list);
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

</script>

</body>
</html>