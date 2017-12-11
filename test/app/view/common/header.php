<html>
<head>
    <meta charset="UTF-8">
    <title>定制BOM</title>
    <link rel="stylesheet" type="text/css" href="<?=PUBLIC_URL?>css/config.css"/>
</head>

<body>
<div class="wrap">
    <!------------------------------左侧菜单栏--------------------------------------->
    <div class="sidebar">
        <img class="sidebar-logo" src="<?=PUBLIC_URL?>img/logo.png" alt="logo"/>
        <ul>
		
		 <li class="sidebar-menu">
                <div class="menu-icon">
                    <img src="<?=PUBLIC_URL?>img/conf.png" alt=""/>
                    <p>
                        <a href="#">配置</a>
                    </p>
                </div>
                <ul class="menu-list clear">
                    <li>
                        <a href="/attributes" <?php if (current_url() != "/attributes"): ?>class="oColor_999" <?php endif; ?>>基础资料</a>
                    </li>
                    <li>
                        <a href="/attributes/data" <?php if (current_url() != "/attributes/data"): ?>class="oColor_999" <?php endif; ?>>数据字典</a>
                    </li>

                    <li>
                        <a href="/attributes" <?php if (current_url() != "/attributes"): ?>class="oColor_999" <?php endif; ?>>商品属性</a>
                    </li>
                    <li>
                        <a href="/attributes" <?php if (current_url() != "/attributes"): ?>class="oColor_999" <?php endif; ?>>属性值 </a>
                    </li>
                    <li>
                        <a href="/cost/scheme" <?php if (current_url() != "/attributes"): ?>class="oColor_999" <?php endif; ?>>工费方案 </a>
                    </li>
                    <li>
                        <a href="/diamond/scheme" <?php if (current_url() != "/attributes"): ?>class="oColor_999" <?php endif; ?>>钻石方案 </a>
                    </li>
                </ul>
            </li>
            <li class="sidebar-menu">
                <div class="menu-icon">
                    <img src="<?=PUBLIC_URL?>img/conf.png" alt=""/>
                    <p>
                        <a href="#">款式</a>
                    </p>
                </div>
                <ul class="menu-list clear">
                    <li>
                        <a href="/style/index" <?php if (current_url() != "/style/index"): ?>class="oColor_999" <?php endif; ?>>款式管理</a>
                    </li>
                </ul>
            </li>
        </ul>
    </div>
    <div class="conten">
        <div class="conten-header">
            <h1 class="otitle">定制BOM</h1>
            <div class="conten-header-r">
                <!--<span class="app">应用</span>
                <span class="info">消息</span>
                <span class="usr">个人</span>
                <span class="call">客服</span>
                <span class="help">帮助</span>-->
                <span class="logout"><a href="/passport/logout">退出 </a></span>
            </div>
        </div>
 