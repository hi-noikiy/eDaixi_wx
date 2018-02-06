/* *
 * Gaode Locate JS API v1.3.7
 * JavaScript Code
 * */

//定位类型筛选（小区、住宅、住宿、大厦、写字楼、公园广场、公司企业、学校）
//var locateType = "100000|100100|100101|100102|100103|100104|100105|100200|100201|120000|" +
//		"120100|120200|120201|120202|120203|120300|120301|120302|120303|120304|190000|190107|" +
//		"170000|170100|170200|170201|170202|170203|170204|170205|170206|170207|170208|170209|170300|" +
//		"190600|190400|190401|190402|190403|190500|190600|110100|110101|110102|110103|110104|110105|" +
//		"110200|110201|110202|110203|110204|110205|110206|110207|141200|141201|141202|141203|141204|141205|141206";

var 
  geolocation,		//浏览器定位对象
  mGeocoder,		//反向地理编码对象
  placeSearch,		//地点搜索	
  jqueryXhr = null,	//jquery-ajax 对象
  timer,			//超时定时器
  errFlag = 0;		//错误标识
//浏览器定位获取位置信息（基于高德）
function autoPostion () {
	//正在定位 ... 
	waiting('正在定位  ');
	if(AMap){
		AMap.plugin('AMap.Geolocation', function () {
			geolocation = new AMap.Geolocation({
				enableHighAccuracy:	true,   //是否使用高精度
				timeout:	10000,    		//超过5秒后停止定位
				maximumAge:	0,  			//定位结果缓存0秒
				convert: true  				//自动偏移坐标，偏移后的坐标为高德坐标
			});
			
			//执行网络定位，获取经纬度
			geolocation.getCurrentPosition();
			//定位失败（返回出错信息），执行回调函数：执行提示，并重置市区联动菜单
			AMap.event.addListener(geolocation, 'error', function(data){
				alert("error");
				alert(data);
				var err = '获取数据失败';
				switch(data.info) {
					case 'PERMISSION_DENIED':
						err += '，浏览器阻止了定位操作';
						break;
					case 'POSITION_UNAVAILBLE':
						err += '，无法获得当前位置';
						break;
					case 'TIMEOUT':
						err += '，定位超时';
						break;
					default:
						err += '，请检查GPS或网络连接是否正常';
						break;
				}
				stopLocate(err);
			}); 
			
			//定位成功（返回经纬度），执行回调函数：获取当前位置信息
			AMap.event.addListener(geolocation, 'complete', function(data){
				alert("complete");
				alert(JSON.stringify(data));
				if(data.accuracy){
					var lng = data.position.getLng();	//经度
					var lat = data.position.getLat();	//纬度
					//度获取当前位置信息
					getLocateInfo(lng, lat, true);
				}else{
					stopLocate();
				}
			});
			
		});
	}else{
		stopLocate('暂无定位数据，请手动填写地址');
	}

}

//根据经纬度获取地址信息
function getLocateInfo(lng, lat, callback) {
	if(AMap){
	    var lnglatXY = new AMap.LngLat(lng, lat);
	    //加载地理编码插件
	    AMap.service(["AMap.Geocoder"], function() {        
	        mGeocoder = new AMap.Geocoder({ 
	            radius: 100,
	            extensions: "base"
	        });
	        //逆地理编码
		    mGeocoder.getAddress(lnglatXY, function(status, result){
	        	if(status === 'complete' && result.info === 'OK'){
	        		//格式化地址（基本行政区信息+具体信息）
	        		var addressFormat = result.regeocode.formattedAddress;
	    			//地址组件（地址各要素信息）
	        		var addressComponent = result.regeocode.addressComponent;
	    			//格式化截取地址名称（适应本站需求）
	    			var addrName = addressFormat.replace(new RegExp(addressComponent.province + addressComponent.city
	    					+ addressComponent.district), '').replace(addressComponent.streetNumber, '');
	    			//自定义拼接详细地址（适应本站需求）
	        		var addrDetail = '';	
	        		if(addressComponent.township)
	        			addrDetail += addressComponent.township;
	    			if(addressComponent.street)
	    				addrDetail += addressComponent.street;
	    			if(addressComponent.streetNumber)
	    				addrDetail += addressComponent.streetNumber.replace(/号/, '') + '号';
	    			//自定义定位信息对象
	        		var locationDate = {
	        			addrName: addrName,
	        			city: addressComponent.city || addressComponent.province, 
	        			citycode: addressComponent.citycode, 
	        			district: addressComponent.district,
	        			addrDetail: addrDetail,
	        			lng: lng,
	        			lat: lat
	        		};
	        		if(callback){
	        			verifyAddress(locationDate);
	        		}else{
	        			return locationDate;
	        		}
	        	}else{
	        		stopLocate();
	        	}
	        });
	    });
	}else{
		stopLocate('暂无定位数据，请手动填写地址');
	}
}

//输入地址自动补全
function autoComplete(cityName, keywords) {
    //关键字查询查询
	var keywords = $.trim(keywords);
    if(!keywords || !cityName){
    	hideSearchList();
    	hideUserWin();
    	return false;
    }else{
    	if(AMap){
    		AMap.service(["AMap.PlaceSearch"], function() {
        	    placeSearch = new AMap.PlaceSearch({
        	    	city: cityName,
        	    	//type: locateType,
        	    	pageSize: 6,
        	    	extensions: "all"
        	    });
                placeSearch.search(keywords, function(status, result){
                	var searchInfo = [];
                	if(status == "complete"){
                		var count = result.poiList.count;
                		count = count > 6 ? 6 : count;
                		if(count > 0){
                			var resultList = "";
                			for(var i=0; i<count; i++){
                				var poiInfo = result.poiList.pois[i];
                				if(poiInfo.cityname.indexOf(cityName) > -1|| poiInfo.adname.indexOf(cityName) > -1){
                					 searchInfo[i] = {
                						addrName: poiInfo.name,			//poi所在地址名称
        		            			city: poiInfo.cityname,			//poi所在城市名称
        		            			citycode: poiInfo.citycode,		//poi所在城市编码
        		            			district: poiInfo.adname,		//poi所在区县名称
        		            			addrDetail: poiInfo.address,	//poi所在具体地址
        		            			lng: poiInfo.location.lng,		//poi所在经度
        		                		lat: poiInfo.location.lat		//poi所在纬度
        		            	    };
                					var addrname = '"' + searchInfo[i].addrName + '"', city = '"' + searchInfo[i].city + '"', citycode = '"' + searchInfo[i].citycode + '"', 
                					district = '"' + searchInfo[i].district + '"',  addrdetail = '"' + searchInfo[i].addrDetail + '"',
                					lng = '"' + searchInfo[i].lng + '"', lat = '"' + searchInfo[i].lat + '"',
                					describe = searchInfo[i].city + " " +searchInfo[i].district + " " + searchInfo[i].addrDetail;
                					resultList += "<div onclick='selectSearchList(" + addrname+ "," + city + "," + citycode  + "," + district + "," 
        	                        			+ addrdetail + "," + lng + "," + lat + ");' style='cursor:pointer;'>" + searchInfo[i].addrName 
        	                        			+ "<br/><span style='color:#999;font-size:0.9em'>" + describe + "</span></div><div class='borderD'></div>";
                				}
                			}
                			if(resultList.length){
                    			resultList += "<div onclick='showUserWin();' style='text-align:center;color:#00dbf5;margin-left:0'>没有想要的地址，尝试手动选择市、区</div>";
                    			showSearchList(resultList);
                			}else{
                				showUserWin();
                			}
                		} else {
                			showUserWin();
                        }
                	}else{
                		showUserWin();
                	}
                });
        	});
    	}else{
    		showUserWin();
    	}
    }
}


/**
 * Gaode Locate Callback
 * JQuery Code
 */
//正在定位 ... 
function waiting(msg){
	$('#wait_msg').text(msg);
	$('.waiting').show();
	setTimeout(function(){
		$('.waiting').on('click', function(){
			stopLocate();
		});
	}, 2000);
}

//定位结束
function locateFinish(){
	$('.waiting').hide();
	$('#location_btn').one('click', function(){
		autoPostion();
	});
}

//停止定位
function stopLocate(){
	$('.waiting').hide();
	var msg = arguments[0] ? arguments[0] : "请检查网络连接，开启GPS" ;		
	showError(msg, true);	
}

//显示地址管理页
function showMainWin(){
	$('#searchList').empty();
	$('#searchWin').hide();
	$('#userWin').hide();
	$('#mainWin').show();
}

//显示搜索自动完成页
function showSearch(){
	var keywords = $.trim($('#addr_name').val());
	if(keywords){
		if(SCFlag == 1){
			var cityName = $('#home_city_sel option:selected').val();
		}else{
			var cityName = $('#cmbCity option:selected').val();
		}
		autoComplete(cityName, keywords);
		$('#searchInp_clear').show();
	}else{
		$('#searchInp_clear').hide();
	}
	$('#mainWin').hide();
	$('#userWin').hide();
	$('#searchWin').show();
	$('#searchInp').focus().val(keywords);
	xbtnStatus($('#searchInp')[0]);
}

//显示搜索列表
function showSearchList(resultList){
	 $('#userWin').hide();
	 $('#searchList').html(resultList).show();
}

//显示用户输入地址页
function showUserWin(){
	if($("#userWin:visible").length == 0){
		if(SCFlag == 1){
    		var cityName = $('#home_city_sel option:selected').val();
    		var areaName = '';
    	}else{
    		var cityName = $('#cmbCity option:selected').val();
    		var areaName = $('#cmbArea option:selected').val();
    	}
		selectedCity2(cityName, areaName);
	}
	setBtnStatus2();
	$('#searchList').empty().hide();
	$('#userWin').show();
	$('#searchInp').focus();
	xbtnStatus($('#searchInp')[0]);
}

//隐藏搜索列表
function hideSearchList(){
	$('#searchList').empty().hide();
}

//隐藏用户输入地址页
function hideUserWin(){
	$('#userWin').hide();
}

//输入关键词自动搜索
function autoSearch(o){
	var keywords = $.trim($(o).val());
    //关键字查询查询
    if(!keywords){
    	hideSearchList();
    	hideUserWin();
    	return false;
    }else{
    	if(SCFlag == 1){
    		var cityName = $('#home_city_sel option:selected').val();
    	}else{
    		var cityName = $('#cmbCity option:selected').val();
    	}
    	autoComplete(cityName, keywords);
    }
}

//选择搜索地址推荐列表，执行回调函数（验证地址是否在服务范围，重置市区联动菜单）
function selectSearchList(addrname, city, citycode, district, addrdetail, lng, lat) {
	var searchInfo = {
   			addrName: addrname,
			city: city, 
			citycode: citycode,
			district: district, 
			addrDetail: addrdetail,
			lng: lng,
			lat: lat
	};
	verifyAddress(searchInfo);
}

//验证当前地址是否在服务范围，重置市区联动菜单
function verifyAddress(adressInfo) {
	//ajax 去服务端验证地址是否在服务范围
	var min = 1, max = 127, reqflag = Math.floor(Math.random() * (max - min + 1) + min);
	//终止之前的未结束的 ajax请求，重新开始新的请求  
	if(jqueryXhr){
		jqueryXhr.abort(); 
  	}
	jqueryXhr = $.ajax({
    	url: verifyUrl,
    	type: "POST",
    	async: true,		//是否异步请求：true 表示异步请求；false 表示同步请求 
    	timeout: 2000, 		//超时时间设置，单位毫秒
    	data: {
    		category_id: category_id,				//品类ID 快洗订单使用
    		city: adressInfo.city,					//城市名（字符串）   必填  
    		area: adressInfo.district,				//区县名（字符串）   必填
    		address_line_1:	adressInfo.addrName,	//定位地址名	      可选
    		customer_lng: adressInfo.lng,  			//定位经度（默认0）  可选
    		customer_lat: adressInfo.lat,   		//定位纬度（默认0）  可选
    		flag: reqflag							//请求标识（整型）   可选
    	},
    	dataType: "json",
    	complete: function (xhr, ts){
    		locateFinish();
    		//请求完成
    		jqueryXhr = null;
    	},
    	success: function (res, ts, xhr) {
    		//请求成功
    		if(res.message.data){
    			var content = res.message.data;
    			//是否在服务范围
    			var inServ = content.on_service;
    			//提交地址在服务范围
    			if(inServ){
    				//设置市区联动菜单（可以切换城市、区域）
    				selectedCity(adressInfo.city, adressInfo.district);
					returnSearchAddr(adressInfo);
    			}
    			//提交地址不在服务范围
    			if(!inServ){
    				var msg = content.message;
    				resetLngLat();
    				$('#addr_name').val('');
    				showError(msg);
    			}
    		}else{
    			selectedCity(adressInfo.city, adressInfo.district);
    			returnSearchAddr(adressInfo);
    		}
    	},
    	error:function (xhr, ts, err){		
			selectedCity(adressInfo.city, adressInfo.district);
			returnSearchAddr(adressInfo);
    	}
    });
}

//清空地址搜索关键词、清空搜索列表
function clearKeyword(){
	$('#searchInp').val('');
	$('#searchList').empty();
}

//定位地址在服务范围，填充地址名称、经纬度
function returnSearchAddr(searchInfo){
	$(":hidden[name='customer_lng']").val(searchInfo.lng);
	$(":hidden[name='customer_lat']").val(searchInfo.lat);
	$('#addr_name').val(searchInfo.addrName);
	$('#home_city').hide();
	$('#cmbArea_wrap').show();
	SCFlag = 2;
	$('#select_wrap').show();
	setSaveBtn();
	showMainWin();
}

//定位地址不在服务范围，清空地址名称、经纬度
function resetLngLat(){
	$(":hidden[name='customer_lng']").val(0);
	$(":hidden[name='customer_lat']").val(0);
}

//定位错误地址
function showError(msg){
	if(errFlag){
		return;
	}
	var reload = arguments[1] ? true : false;
	if(reload){
		errFlag = 1;
	}
	$('#show_mes').html(msg);
	$('#codFloat').show();
	setTimeout(function(){
		$('#show_mes').html('');
		$('#codFloat').hide();
		if(reload){
			var url = window.location.href;
			window.location.href = url.replace(/&pos=no/, '') + '&pos=no';
		}
	},2000);
}

//表单提交验证
function checkform(){
	var username = $.trim($("#username").val());
	var gender =  $(":radio[name='gender']:checked").length;
	var tel = $.trim($("#tel").val());
	var addrName = $.trim($("#addr_name").val());
	var city = $('#cmbCity option:selected').val();
	var area = $('#cmbArea option:selected').val();
	var addrDetail = $.trim($("#detail").val());
	if(username == ""){
		showError('姓名不能为空');
	   	return false;
	}
	if(gender < 1){
	   	showError('您是女士还是先生呢？');
	   	return false;
	}
	var re = /^1[3-8]\d{9}$/;
	if(tel == ''){
		showError('手机号不能为空');
	   	return false;
	}else if (!re.test(tel)) {
	  	showError('请正确填写手机号码');
	  	return false;
	};
	if(!city || city == "" || city == '请选择城市'){
		showError('城市不能为空');
		return false;
	}
	if(addrName == ""){
		showError('小区名或者大厦名不能为空');
	 	return false;
	}
	if(!area || area == "" || area == '请先选择区域'){
		showError('区域不能为空');
		return false;
	}
	if(addrDetail == ""){
		showError('详细地址不能为空');
		return false;
	}
	//area_id 赋值
	$('#area_id').val($('#cmbArea').find('option:selected').attr('rel'));
  $('#save_address').prop('disabled', true).css({'opacity':'.5'});
}

//设置“清除”按钮显示状态
function xbtnStatus(o){
	var val = $(o).val();
	var xid = $(o).attr('id') + '_clear';
	if($.trim(val).length > 0 ){
		$('#' + xid).show();
	}else{
		$('#' + xid).hide();
	}
}

//清空输入内容
function clearInput(o, id){
	$('#' + id).val('');
	$(o).hide();
	if('searchInp' == id){
		$('#searchList').empty();
		
    $('#confirm_address').prop('disabled', true).css({'opacity':'.5'});
		$('#userWin').hide();
	}
	if('tel' == id || 'username' == id || 'addr_name' == id || 'detail' == id ){
		
    $('#save_address').prop('disabled', true).css({'opacity':'.5'});
	}
}

//设置“保存”按钮显示状态
function setSaveBtn(){
	var username = $.trim($("#username").val());
	var gender =  $(":radio[name='gender']:checked").length;
	var tel = $.trim($("#tel").val());
	var addrName = $.trim($("#addr_name").val());
	var city = $('#cmbCity option:selected').val();
 	var area = $('#cmbArea option:selected').val();
 	var addrDetail = $.trim($("#detail").val());
 	var tel_flag = true;
 	var re = /^1[3-8]\d{9}$/;
 	if (tel == '' || !re.test(tel)) {
  		tel_flag = false;
	}
	if(username && gender && tel_flag && addrName && city && area && addrDetail){
  		
      $('#save_address').prop('disabled', false).css({'opacity':'1'});
  		return true;
	}else{
      $('#save_address').prop('disabled', true).css({'opacity':'.5'});
  		return false;
	}
}

//修改城市下拉菜单值
function selectedCity(selCity, selArea){
	if(selCity){
		selCity = selCity.replace(/市/, '');
		var selCityOpt = $('#cmbCity').find("option[value='" + selCity + "']");
		selCityOpt.prop('selected', true);
		var city_sn = selCityOpt.attr('city_sn');
		var area_options = $('#area_options_list option.' + city_sn).clone(true);
		$('#cmbArea option:not(.default)').remove();
		$('#cmbArea').append(area_options);
		if(selArea){
			var selAreaOpt = $('#cmbArea').find("option[value^='" + selArea + "']");
			//此处增加area_id的赋值
			//$('#area_id').val(selAreaOpt.attr('rel'));
			selAreaOpt.prop('selected', true);
		}else{
			$('#cmbArea option.default').prop('selected', true);
		}
	}
	resetLngLat();
}

//用户体验差--待后期删除
function selectedCity2(selCity, selArea){
	if(selCity){
		var selCityOpt = $('#user_city').find("option[value='" + selCity + "']");
		selCityOpt.prop('selected', true);
		var city_sn = selCityOpt.attr('city_sn');
		var area_options = $('#area_options_list option.' + city_sn).clone(true);
		$('#user_area option:not(.default)').remove();
		$('#user_area').append(area_options);
		if(selArea){
			var selAreaOpt = $('#user_area').find("option[value^='" + selArea + "']");
			selAreaOpt.prop('selected', true);
		}else{
			$('#user_area option.default').prop('selected', true);
		}
	}
}

//用户体验差--待后期删除
function returnKeyword2(){
	var keywords = $.trim($('#searchInp').val());
	var u_city = $('#user_city option:selected').val();
  	var u_area = $('#user_area option:selected').val();
  	var addrName = $.trim($('#addr_name').val());
	var city = $('#cmbCity option:selected').val();
  	var area = $('#cmbArea option:selected').val();
	if(keywords != addrName || u_city != city || u_area != area){
		resetLngLat();
	  	selectedCity(u_city, u_area);
		$('#addr_name').val(keywords);
	}
	$('#home_city').hide();
	$('#cmbArea_wrap').show();
	SCFlag = 2;
	$('#select_wrap').show();
	setSaveBtn();
	showMainWin();
}

//用户体验差--待后期删除
function setBtnStatus2(){
	var u_city = $('#user_city option:selected').val();
 	var u_area = $('#user_area option:selected').val();
 	var u_addr = $.trim($('#searchInp').val());
 	if(u_city && u_area && u_addr){
  	$('#confirm_address').prop('disabled', false).css({'opacity':'1'});
  		return true;
	}else{
  		$('#confirm_address').prop('disabled', true).css({'opacity':'.5'});

  		return false;
	}
}
