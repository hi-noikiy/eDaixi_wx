<!DOCTYPE HTML>
<html>
<head>
    <title>装x技术哪家强</title>
    <meta name="viewport" content="user-scalable=no"/>
    <meta charset="utf-8"/>
    <meta name="author" content="html5china"/>
    <meta name="screen-orientation" content="portrait"/>
    <meta name="full-screen" content="yes"/>
    <meta name="x5-fullscreen" content="true"/>
    <meta name="360-fullscreen" content="true"/>
    <script>

        //百度统计初始化
        var _hmt = _hmt || [];
    </script>
</head>
<?php require('include.php');?>
<body>
<script>
    //globals
    // 微信分享的数据
    window.wxData = {
        //微信分享图片地址
        "imgUrl":'http://game.edaixi.cn/res/icon.jpg',
        //微信分享地址链接
        "link":'http://game.edaixi.cn',
        //默认分享内容
        "desc":"121",
        //分享标题
        "title":"装x技术哪家强"
    };

    g_prejectPrePath="";
    g_time=15;//游戏时间
    g_userId="noname";//用户id
    g_delayTime=3;
    g_favor=0;
    g_isMi4=false;

    //小米4
    var agent=navigator.userAgent;
    var pos=agent.indexOf("MI 4");

    if(pos!=-1){
        g_isMi4=true;
    }
</script>
<canvas id="gameCanvas" width="960" height="640" ></canvas>
<!--<script src="../../frameworks/cocos2d-html5/CCBoot.js"></script>-->
<!--<script src="main.js"></script>-->
<script src="myRun.js"></script>
<script>
    function GetRequest()
        {
            var url = location.search; //获取url中"?"符后的字串
            var theRequest = new Object();
            if (url.indexOf("?") != -1) {
                var str = url.substr(1);
                var strs = str.split("&");
                for(var i = 0; i < strs.length; i ++) {
                    theRequest[strs[i].split("=")[0]]=unescape(strs[i].split("=")[1]);
                }
            }
        return theRequest;
    }
    var req=GetRequest();
    var userId=req["user_id"]

    if(userId!=undefined)
    {
        if(userId!=""){
            g_userId=userId;
        }
        trace("game","play",g_userId);

        var canvas=document.getElementById("gameCanvas");
        var url="https://mp.weixin.qq.com/s?__biz=MzA3NjA4OTkwNQ==&mid=211135655&idx=1&sn=595200cf406d3c8aa3160031a74b700f#rd";
        window.open(url,"popup","width="+canvas.width+",height="+canvas.height);
    }

    function trace(type,op,tag)
    {
        _hmt.push(['_trackEvent', type, op, tag]);
    }

    function getAward(award)
    {
        var canvas=document.getElementById("gameCanvas");
        var url='https://wx.rongchain.com/mobile.php?act=oauth&eid=484&weid=5&extra='+award;
        window.open(url,"popup","width="+canvas.width+",height="+canvas.height);
    }

    function setCookie(name,value,time)
    {
        var strmsec = getsec(time);
        var exp = new Date();
        exp.setTime(exp.getTime() + strmsec*1);
        document.cookie = name + "="+ value + ";expires=" + exp.toGMTString();
    }

    function getsec(str)
    {
        var str1=str.substring(1,str.length)*1;
        var str2=str.substring(0,1);
        if (str2=="s")
        {
            return str1*1000;
        }
        else if (str2=="h")
        {
            return str1*60*60*1000;
        }
        else if (str2=="d")
        {
            return str1*24*60*60*1000;
        }
        else if(str=="m")
        {
            return str*60*1000;
        }
    }

    function getCookie(name)
    {
        var arr,reg=new RegExp("(^| )"+name+"=([^;]*)(;|$)");

        if(arr=document.cookie.match(reg))

            return arr[2];
        else
            return null;
    }

    function shareWx(content){
        window.wxData.desc = content;

        wx.onMenuShareAppMessage({
          title: '装x技术哪家强',
          desc: window.wxData.desc,
          link: 'http://game.edaixi.com?v=4',
          imgUrl: 'http://game.edaixi.com/res/icon.jpg?v=4',
          trigger: function (res) {
            //alert('用户点击发送给朋友');
          },
          success: function (res) {
            trace('share','share', g_userId);
            getAward(g_favor);
          },
          cancel: function (res) {
            //alert('已取消');
          },
          fail: function (res) {
            alert(JSON.stringify(res));
          }
        });
        //alert('已注册获取“发送给朋友”状态事件');

        wx.onMenuShareTimeline({
          title: window.wxData.desc,
          link:  'http://game.edaixi.com?v=4',
          imgUrl: 'http://game.edaixi.com/res/icon.jpg?v=4',
          trigger: function (res) {
            alert('用户点击分享到朋友圈');
          },
	  cancel: function(res){
	     alert('1231231231');	
	  }
          success: function (res) {
            trace('share','share', g_userId);
           // getAward(g_favor);
          },
          fail: function (res) {
            //alert(JSON.stringify(res));
          }
        });

        wx.onMenuShareWeibo({
          title: '装x技术哪家强',
          desc: window.wxData.desc,
          link: 'http://game.edaixi.com?v=4',
          imgUrl: 'http://game.edaixi.com/res/icon.jpg?v=4',
          trigger: function (res) {
            //alert('用户点击分享到微博');
          },
          complete: function (res) {
            //alert(JSON.stringify(res));
          },
          success: function (res) {
           trace('share','share', g_userId);
            getAward(g_favor);
          },
          cancel: function (res) {
            //alert('已取消');
          },
          fail: function (res) {
            alert(JSON.stringify(res));
          }
        });
        //alert(window.wxData.desc);
    }

</script>

<script type="text/javascript">
    var _bdhmProtocol = (("https:" == document.location.protocol) ? " https://" : " http://");
    document.write(unescape("%3Cscript src='" + _bdhmProtocol + "hm.baidu.com/h.js%3F6a2b1ae44bc3802c2bd11f8f84398994' type='text/javascript'%3E%3C/script%3E"));
</script>

</body>
</html>
