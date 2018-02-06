$.ajax({
    url: '/api.php?m=wap&act=homepage&do=ajax_get_foot',
      // url: 'test/tongji.json',
      type: 'GET',
      dataType: 'json'
    })

    .done(function(data) {  
        tongji(data);     
    })

    .fail(function() {
        alert('网络繁忙，请稍后再试')
    })


    function tongji(data) {
      var userAgent = navigator.userAgent;
      if(userAgent.indexOf("baiduboxapp") != -1){
        customshare();
      }
      //手机百度分享图标自定义
      function customshare(){
        window.BoxShareData = {
          successcallback: "onSuccess",
          errorcallback: "onFail",
          options: {
            type: "url",
            content: "洗衣就用e袋洗，幸福生活每一天",
            iconUrl: "http://apps3.bdimg.com/store/static/kvt/afdedad50e7414a224f7e530698f4d02.png",
            imageUrl: "http://apps3.bdimg.com/store/static/kvt/afdedad50e7414a224f7e530698f4d02.png",
            linkUrl: location.href,
            mediaType: "all",
            title: "e袋洗"
          }
        }
      }
      //获取URL参数方法
      function getQueryString(name) {
        var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)", "i");
        var r = window.location.search.substr(1).match(reg);
        if (r != null) return unescape(r[2]); return null;
      }
      //搜狗js动态引入
      if(getQueryString("mark")=="ae891810-4aef-11e5-ade6-f80f41fd4734") {

        var head= document.getElementsByTagName('head')[0]; 
        var script= document.createElement('script'); 
        script.type= 'text/javascript';
        script.src= 'http://fuwu.wap.sogou.com/static/partner.js'; 
        script.setAttribute("sogouid","054");
        head.appendChild(script);  
        
        $('body').css({'margin-top': '40px'});
        
      }


    // 百度统计

    if (data.data.statistics.baidu) { 
       
      var _hmt = _hmt || [];
      (function() {
        var hm = document.createElement("script");
            if (data.data.user_type == 1){
              hm.src = "//hm.baidu.com/hm.js?6eb8d3e8f50829c93d53de9c63a9ebcd";
            }
              
            else{
              hm.src = "//hm.baidu.com/hm.js?c69820aa19563b0688a6d8ea8d21f158";
            }
              
          var s = document.getElementsByTagName("script")[0]; 
        s.parentNode.insertBefore(hm, s);
      })();
    };

    // Piwik
      if (data.data.statistics.piwik ){
        var _paq = _paq || [];
        _paq.push(['trackPageView']);
        _paq.push(['enableLinkTracking']);
        (function() {
            if (data.data.piwik == 1){
              var u = "//analysis.edaixi.com/";
            }
              
            else{
              var u = "//analysis10.edaixi.cn/";
            }
              

            var _u = "//cdnwww.edaixi.com/piwik.js";
            // var _u = "http://wx.rongchain.com/1468602683/framework/style/js/";
            _paq.push(['setTrackerUrl', u+'piwik.php']);
            _paq.push(['setSiteId', 2]);

          
            if (data.data.userId) {
              _paq.push(['setUserId', userId])
            };

          var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
          g.type='text/javascript'; g.async=true; g.defer=true; g.src=_u; s.parentNode.insertBefore(g,s);
        })();

      }
    }
    

  