<?php 
// $k = 0;
// $fp_tmp = fopen("/tmp/log2_3.txt","r");
// for($j=1;! feof($fp_tmp);$j++) { 
// 	$arr[] = fgets($fp_tmp);	
// 	//echo $arr[$j-1];
// 	$k ++;
//  }
// //var_dump($k);exit;
//  //var_dump($k);exit;
// fclose($fp_tmp);
	require '../framework/bootstrap.inc.php';
	$fp = fopen("/tmp/20150615_user.txt", "r"); 
	$host='127.0.0.1';
	$port='6379';
	$redis=new Redis();
	$redis->pconnect($host,$port);
if($fp) { 
	$arr = array();
	for($i=1;! feof($fp);$i++) { 
		//echo "----------------------行".$i." : ".fgets($fp); 
		$str = fgets($fp);
		$redis->set('user_'.md5($i),$str);
		// if(strpos($str,'<xml>')){
		// 	$str_1 = explode('<xml>', $str);
		// 	$str = '<xml>'.$str_1[1];
		// 	$str_tmp = $str;
		// 	$sign = 1;
		// }
		//var_dump(strpos($str,'</xml>') === 0);
		// if(strpos($str,'</xml>') === 0){
		// 	$str_tmp .= $str;
		// 	$sign = 0;
		// 	$arr[] = $str_tmp;
		// 	$j++;
		// }
		// if($sign == 1){
		// 	if(strpos($str,'TEMPLATESENDJOBFINISH')){
		// 		$str_tmp = '';
		// 		$sign = 0;
		// 	}else{
		// 		$str_tmp = $str_tmp.'  '.$str;
		// 	}
		// 	//var_dump($str_tmp);
		// } 
		// //echo $arr;
		// if($i > 2){
		//  	var_dump($str);
		//  	exit;

		//  }
	}
		
}
$res = $redis->get('user_'.md5(10));
var_dump(explode(',', $res));
fclose($fp); 

?> 