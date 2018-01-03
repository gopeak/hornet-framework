
<p align="center">
	<a href="https://github.com/gopeak/hornet-framework">Hornet-framework</a>
</p>
<h3 align="center">轻量级,基于PHP7的开发框架 <!-- Serve Confidently --></h3>
<p align="center">Hornet-framework目的是快速的轻松的构建一个高性能,可扩展,易于维护的Web应用程序或站点</p>
<p align="center">
	<a href="#"><img src="https://img.shields.io/travis/mholt/caddy.svg?label=linux+build"></a>
	<a href="#"><img src="https://img.shields.io/appveyor/ci/mholt/caddy.svg?label=windows+build"></a>
	   
</p>
<p align="center">
	<a href="http://192.168.3.213/ismond/xphp/repository/archive.zip?ref=master">下载</a> ·
	<a href="http://192.168.3.213/ismond/xphp/wikis/home">文档</a> ·
	<a href="#">社区</a>
</p>

Hornet-framework 具有高性能,轻量级,易于上手,功能完备的PHP LMVC 开发框架.
LMVC分别是 Logic逻辑 Model模型 View视图 Ctrl控制器，与传统的MVC框架比多一层Logic层，目的是解决在复杂的应用系统时，逻辑代码混杂于Model或Ctrl之间的问题。 
 

## 功能特性

- **轻量级**  尽可能少的内存和cpu消耗,较少的调用堆栈 
- **LMVC开发模式**  增强传统的MVC模式
- **多项目支持** 多个项目可以无缝的共用一套开发框架,开发框架代码不会引用或依赖任何项目的逻辑代码
- **自定义错误处理** 内置自定义处理,在测试或正式环境中还可启用错误邮件发送功能，实时收到系统的错误信息
- **Http API支持** 创建对外的Api接口十分简单 
- **自动检验Api返回格式** 解决PHP开发Api接口返回数据类型和格式不稳定不可靠的问题
- **自定义API返回格式** 不同的项目可以有不同的api返回格式
- **动态加载配置文件**  
- **多环境配置** 本地,开发,测试,正式环境有不同的配置文件夹，不同环境切换轻松简单
- **整合xhprof性能分析**  
- **支持Swoole异步处理**  
- **伪静态** 
- **自定义Session处理** 
- **项目环境检查** 
- **易于测试** 整合了PHPUNIT框架并区别功能和单元测试
- **封装PDO抽象类**  
- **自动回收资源**  

## LMVC开发模式
![lvmc](http://192.168.3.213/ismond/xphp/uploads/4ba3a2b1db4af130524e65e87696d946/lvmc.jpg)

## 待完成功能
- **松耦合的设计**   
- **连贯式的Sql语句查询构建器**   
- **增加项目运维平台**   
- **日志处理** ,系统日志,错误日志,逻辑日志不同处理,同时提供查询页面
- **安全性增强**  
- **队列处理**  
 
