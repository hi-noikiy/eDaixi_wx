<?php
require('jssdk.php');
global $_W;
$jssdk = new JSSDK($_W['config']['app']['appid'], $_W['config']['app']['secret']);
//此处为了处理dns，以及负载均衡无法将https传递给服务器的问题
$signPackage = array();
$signPackage['http'] = $jssdk->GetSignPackage();
$signPackage['https'] = $jssdk->GetSignPackage('https://');
?>
<script src = "https://res.wx.qq.com/open/js/jweixin-1.0.0.js"></script>
<script type="text/javascript">
 var schema = window.location.protocol;
 schema = schema.replace(':','');
 if(schema == 'http'){
    wx.config({
        debug:false,
        appId: '<?php echo $signPackage["http"]["appId"];?>',
        timestamp: <?php echo $signPackage["http"]["timestamp"];?>,
        nonceStr: '<?php echo $signPackage["http"]["nonceStr"];?>',
        signature: '<?php echo $signPackage["http"]["signature"];?>',
        jsApiList: [
            // 所有要调用的 API 都要加到这个列表中
            'checkJsApi',
            'onMenuShareTimeline',
            'onMenuShareAppMessage',
            'hideOptionMenu',
            'getLocation'
        ]
    });

 }else if(schema == 'https'){
    wx.config({
        debug:false,
        appId: '<?php echo $signPackage["https"]["appId"];?>',
        timestamp: <?php echo $signPackage["https"]["timestamp"];?>,
        nonceStr: '<?php echo $signPackage["https"]["nonceStr"];?>',
        signature: '<?php echo $signPackage["https"]["signature"];?>',
        jsApiList: [
            // 所有要调用的 API 都要加到这个列表中
            'checkJsApi',
            'onMenuShareTimeline',
            'onMenuShareAppMessage',
            'hideOptionMenu',
            'getLocation'
        ]
    });
 }
</script>
