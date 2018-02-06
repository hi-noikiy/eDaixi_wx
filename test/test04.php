<!DOCTYPEhtml> 
<html> 
<head> 
<script> 
if (navigator.geolocation) {  
alert( ' 你的浏览器支持 geolocation ' );  
}else{  
alert( ' 你的浏览器不支持 geolocation ' )  
}  
navigator.geolocation.getCurrentPosition( getPositionSuccess , getPositionError ); 

function getPositionSuccess( position ){  
var lat = position.coords.latitude;  
var lng = position.coords.longitude;  
alert(lat);
document.write( "您所在的位置： 经度" + lat + "，纬度" + lng );  
}  
function getPositionError(error){  
switch(error.code){  
case error.TIMEOUT :  
alert( " 连接超时，请重试 " );  
break;  
case error.PERMISSION_DENIED :  
alert( " 您拒绝了使用位置共享服务，查询已取消 " );  
break;  
case error.POSITION_UNAVAILABLE :  
alert( " 亲爱的火星网友，非常抱歉，我们暂时无法为您所在的星球提供位置服务 " );  
break;  
}  
}  
/*Locationfunctionshere*/ 
</script> 
</head> 
<body> 

<input type='button' value='get location' onclick='getUserLocation()'/> 
<div id='locationData'> asdfasdfas
</div> 

</body> 
</html> 