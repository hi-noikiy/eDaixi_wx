/**
 * 封装基于高德地图的地理位置定位插件
 */
;(function(AMap, $){
  var 
    locate = {},
    geolocation, // 高德地理定位对象
    flag = true, // 定位标识
    $btn, // 定位按钮
    overtime, // 超时时间
    callback; // 定位成功回调
	if(AMap){
	  // 执行定位，获取经纬度
	  AMap.autoLocate = function(opt){
		  init(opt);
		  if(flag){
			  flag = false;
	          //console.log('正在定位...');
	          AMap.plugin('AMap.Geolocation', function () {
	                  geolocation = new AMap.Geolocation({
	                  enableHighAccuracy: true,   //是否使用高精度
	                  timeout: overtime,          //超过5秒后停止定位
	                  maximumAge: 0,              //定位结果缓存0秒
	                  convert: true               //自动偏移坐标，偏移后的坐标为高德坐标
	              });
	              
	              //执行网络定位
	              geolocation.getCurrentPosition();
	            
	              //定位完成
	              AMap.event.addListener(geolocation, 'complete', function(data){
	            	  flag = true;
	                  if(data.accuracy){ // 定位成功，获得经纬度
	                	  AMap.lng = data.position.getLng();   //经度
	                	  AMap.lat = data.position.getLat();   //纬度
	                      finishLocate(true);
	                  }else{ // 定位失败，结束定位
	                	  AMap.lng = '';   //经度
	                	  AMap.lat = '';   //纬度
	                      finishLocate(false);
	                  }
	              });
	          });
		  }
	  }
	}else{
		finishLocate(false);
	}
  
  // 初始化
  var init = function(opt){
	 $btn = opt.btn, // 定位按钮
	 overtime = opt.overtime, // 超时时间
	 callback = opt.callback; // 定位成功回调
  }
  
  // 结束定位
  var finishLocate = function(bool){
	  if(bool){
		  // 定位成功
		  //alert("autoLocate：\r\nlng：" + AMap.lng + "\r\nlat：" + AMap.lat);
		  callback(AMap.lng, AMap.lat);
	  }else{
		  // 定位失败 ...
		  //console.log('定位失败！');
	  }
  };
  
  return AMap;
})(window.AMap, jQuery);