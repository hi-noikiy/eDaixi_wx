<?php
      $host='127.0.0.1';
       $port='11211';
       $mem=new Memcache();
        $mem->connect($host,$port);
        $items=$mem->getExtendedStats ('items');
        $items=$items["$host:$port"]['items'];
        echo '<pre>'; var_dump($items); echo '</pre>';
        $len=count($items);
        for($i=2;$i<$len;$i++){
            $number=$items[$i]['number'];
            $str=$mem->getExtendedStats ("cachedump",$number,0);
            echo '<pre>';var_dump($str); echo '</pre>';
            $line=$str["$host:$port"];
            if( is_array($line) && count($line)>0){
               foreach($line as $key=>$value){
                 echo $key.'=>';
                 print_r($mem->get($key));
                 echo "/r/n";
                }
       	}
       	//exit;
     }
?>