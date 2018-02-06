<?php

	class Address extends BaseModule {

		function __construct(){
			global $_W;
			parent::__construct();
			$this->model_name = 'address';
			$this->open_server = new OpenServer($_W['config'],$this->user_info);
		}

		/******************* 写字楼快洗begin *******************/
		
		// ajax 获取写字楼
		public function ajax_search_office(){
		    global $_GPC;
		    $user_id = $this->user_info['user_id'];
		    $user_type = $this->user_info['user_type'];
		    $city_id = $_GPC['city_id'] ?: '';
		    // 搜索类型:0 定位写字楼     1 搜索写字楼      2 全部写字楼
		    $search_type = isset($_GPC['search_type']) ? $_GPC['search_type'] : ''; 
		    $page = $_GPC['page'] ?: ''; // 页码
		    $per_page = $_GPC['per_page'] ?: ''; // 偏移量
		    $search_text = $_GPC['search_text'] ?: '';
		    $lat = $_GPC['lat'] ?: '';  // 纬度
		    $lng = $_GPC['lng'] ?: '';  // 经度
		    if (!$city_id || false === $search_type){
		    	echo_json(false, '', '参数错误');
		    }
		    $resp = $this->open_server->search_office_building($user_id, $city_id, $user_type, $search_type, $page, $per_page, $search_text, $lat, $lng);
		    if ($resp['ret'] === false){
		    	echo_json(false, '', '网络错误');
		    }
		    echo_json(true, $resp);

		    if ($search_type == 0){ // 定位写字楼
		    	$office = $resp['locate_building'];
		    }else if($search_type == 1){ // 搜索写字楼
		    	$office = $resp['search_buildings'];
		    }else if($search_type == 2){ // 全部写字楼下一页
		    	$office = $resp['office'];
		    }
		    $hcount = count($office);
		    if($hcount){
		    	$result['hcount'] = $hcount;
		    	$result['office'] = $office;
		    }else{
		    	$result['hcount'] = 0;
		    	$result['office'] = null;
		    }
		    message($result, '', 'ajax');
		}
	
		// 写字楼列表 -- 下一页
		public function ajax_next_office(){
		    global $_W,$_GPC;
		    $user_id = $this->user_info['user_id'];
		    $user_type = $this->user_info['user_type'];
		    $city_id = $_GPC['city_id'];
		    $search_type = 2; // 搜索类型(全部酒店列表)
		    $page = intval($_GPC['page']); // 页码
		    $per_page = intval($_GPC['per_page']); // 偏移量
		    $search_text = '';
		    $lat = '';   // 纬度
		    $lng = '';   // 经度
		    
		    $resp = $this->open_server->search_office_building($user_id, $city_id, $user_type, $search_type, $page, $per_page, $search_text, $lat, $lng);
		    $all_buildings = $resp['all_buildings'];
		    $rows_count = count($all_buildings);
		    $html = '';
		    foreach ($all_buildings as $key => $item){
		        $html .= '<li class="position_list hotel-item" data-page="' . $page .'" data-hotel-id="' . $item['id'] .'" data-hotel-name="' . $item['title'] . '" data-hotel-area="' . $item['area'] .'" data-hotel-area-id="' . $item['area_id'] . '">
			            <div class="address_img">
			               <img src="' . assets_link('/framework/style/images/address-position.png') . '">
			            </div>
			            <div class="detail_hotel" >
			               <p class="hotel_name">' . $item['title'] .'</p>
			               <p class="hotel_area">' . $item['city'] . $item['area'] . $item['street'] . $item['address'] . '</p>
			            </div>
			         </li>
			         <div class="borderD"></div>';
		    }
		    $result['rows_count'] = $rows_count;
		    $result['html'] = compress_html($html);
		    message($result, '', 'ajax');
		}

		// 写字楼地址
		public function office_address(){
			global $_GPC,$_W;
			$user_id = $this->user_info['user_id'];
			$user_type = $this->user_info['user_type'];
			$city_id = $_GPC['city_id'] ?: get_user_city()['city_id'];

			// 返回订单页携带参数
			$query_data = array(
			    'city_id' => $city_id,
			    'price_read'	=>	1,
			    'category_id'	=>	$_GPC['category_id'],
			    'room'	=>	$_GPC['room'],
			    'user_name'	=>	$_GPC['user_name'],
			    'tel'	=>	$_GPC['tel'],
			    'washing_date_text'	=>	$_GPC['washing_date_text'],
			    'view_text'	=>	$_GPC['view_text'],
			    'washing_date'	=>	$_GPC['washing_date'],
			    'washing_time'	=>	$_GPC['washing_time'],
			    'time_range'	=>	$_GPC['time_range'],
			    'comment'	=>	$_GPC['comment'],
			);
			$office_order_url = add_params('/new_weixin/view/offices_fast_place_order.html', $query_data);
			    
			// 搜索类型：0 定位写字楼  1 搜索写字楼  2 附近+全部写字楼
			$search_type = 2;
			$page = 1; // 页码
			$per_page = 7; // 偏移量
			$search_text = '';
			$lat = $_GPC['lat'];   // 纬度
			$lng = $_GPC['lng'];   // 经度
			$resp = $this->open_server->search_office_building($user_id, $city_id, $user_type, $search_type, $page, $per_page, $search_text, $lat, $lng);
			// 附近写字楼
			$near_buildings = $resp['near_buildings'];
			$near_count = count($near_buildings);
			// 全部写字楼
			$all_buildings = $resp['all_buildings'];
			$all_buildings_count = count($all_buildings);
			$total_pages = $resp['total_pages'];
			
			include $this->template('office_address');
		}
		/******************* 写字楼快洗end *******************/

		public function manage_address(){
			global $_GPC,$_W;
			
			$user_id = $this->user_info['user_id'];
			$user_type = $this->user_info['user_type'];
			$client_id = $this->user_info['client_id'];
			$from_user = $this->user_info['from_user'];
			$mobile = $this->user_info["is_login"];
			$gender = null;
			// 添加完地址的返回页（来源于余额商城添加地址时）
			// $back = null;
			if(!empty($_GPC['category_id'])){
				$category_id = $_GPC['category_id'];
			}
			if(!empty($_GPC['tmp_order'])){
			    // 扫码下单ID
			    $tmp_order = $_GPC['tmp_order'];
			}
			if(!empty($_GPC['address_id'])){
				$address_id = $_GPC['address_id'];				
			}
			if(!empty($_GPC['replace_order'])){
				$replace_order = $_GPC['replace_order'];
			}
			if(!empty($_GPC['link_from'])){
				$link_from = $_GPC['link_from'];
			}
			if(!empty($_GPC['back'])){
				$back = $_GPC['back'];
			}
			$sign = $_GPC['sign'] ? $_GPC['sign'] : 'create';
			
			// 删除地址
			if('delete' == $sign){
				$res = $this->open_server->delete_address($user_id, $address_id);
				if($res['ret']){
					# 更新缓存地址列表，校验地址操作权限时读取
					redis()->srem('address:' . $user_id, $address_id);
					
					$result['msg'] = '地址删除成功';
					$result['state'] = 1;
					message($result, '', "ajax");
				}else{
					$result['msg'] = '地址删除失败';
					$result['state'] = 0;
					message($result, '', "ajax");
				}
				return;
			}
			
			// 获取用户首页城市
			$user_city = get_user_city();
			$city_id = $user_city['city_id'];
			$city_name = $user_city['city_name'];
			$home_city = ($city_name ? $city_name : '北京');
			$area_name = '';
			
			// 获取“城市/区域”列表
			$cities_areas = $this->open_server->cities_options();
			$all_area_opts = $this->area_opts($cities_areas, $city_name, '', true);
			// 验证是否在服务范围 URL
			$verify_url = create_url('address/ajax_verify_address');
			// 添加地址
			if('create' == $sign){			
				$city_name = $home_city;
				$area_name = '';
				$city_options = $this->city_opts($cities_areas, $city_name);
				$area_options = $this->area_opts($cities_areas, $city_name, '');
			}

			// 编辑地址
			if('update' == $sign){
				$address = encrypt('DE', $_GPC['address']);
				$city_name = $address['city'];
				$area_name = $address['area'];
				$mobile = $address['tel'] ? $address['tel'] : '';
				$gender = $address['gender'] ? $address['gender'] : '未知';
				$city_options = $this->city_opts($cities_areas, $city_name);
				$area_options = $this->area_opts($cities_areas, $city_name, $area_name);
			}
			
			include $this->template('manage_address');
		}

		public function update_address(){
			global $_GPC,$_W;
			// 注意：变量名必须与数组下标一致
			$data = array();
			$data['user_id'] = $user_id = $this->user_info['user_id'];
			// $data['user_type'] = $user_type = $this->user_info['user_type'];
			// $data['client_id'] = $client_id = $this->user_info['client_id'];
			// $data['province'] = $province = '';
			$data['username'] = $username = $_GPC['username'];
			$data['city'] = $city = $_GPC['city'];
			$data['area'] = $area = $_GPC['area'];
			$data['area_id'] = $area_id = $_GPC['area_id'] ?: 0 ;
			$data['city_id'] = check_city_name($city);
			$data['tel'] = $tel = str_replace(PHP_EOL, '', $_GPC['tel']);
			$data['gender'] = $gender = $_GPC['gender'];
			$data['address_line_1'] = $address_line_1 = trim($_GPC['address_line_1']);
			$data['address_line_2'] = $address_line_2 = trim($_GPC['address_line_2']);
			$data['customer_lng'] = $customer_lng = $_GPC['customer_lng'];
			$data['customer_lat'] = $customer_lat = $_GPC['customer_lat'];
			$data['category_id'] = $category_id = $_GPC['category_id'];
			if($category_id && !is_numeric($category_id)){
				$data['category_id'] = -1;
				$data['sub_category_ids'] = $category_id;
			}

			if(!empty($_GPC['sign'])){
				$sign = $_GPC['sign'];
			}
			if(!empty($_GPC['tmp_order'])){
				$tmp_order = $_GPC['tmp_order'];
			}
			if(!empty($_GPC['replace_order'])){
				$replace_order = $_GPC['replace_order'];
			}
			if(!empty($_GPC['link_from'])){
				$link_from = $_GPC['link_from'];
			}
			if(!empty($_GPC['back'])){
				$back = $_GPC['back'];
			}

			$query_data = array(
			        'tmp_order' => $tmp_order,
					'category_id' => $category_id,
					'replace_order' => $replace_order,
					'link_from'	=> $link_from,
					'back' => $back,
			);
			// 个人中心
			if('address_list' == $link_from){
				$redirec_url = create_url('address/address_list');
			} else if('balance_mall' == $link_from) {
				if(isset($back) && strpos($back, 'lidada')){
					// 添加地址来源于余额商城，完成后跳回余额商城
					$redirec_url = urldecode($back);
				}else{
					$redirec_url = create_url('address/address_list');
				}
			}else{
			    if ($link_from && $back){
			    	// 下单时带来的
			    	$redirec_url = add_params(urldecode($back), $query_data);
			    }else{
			        $redirec_url = create_url('homepage/order_place', $query_data);
			    }
			}
			if('create' == $sign){
				// 添加地址
				$res = $this->open_server->create_address($data);
			}else if('update' == $sign){
				// 编辑地址
				$data['address_id'] =  $_GPC['address_id'];
				$res = $this->open_server->update_address($data);
			}
			if($res['ret']){
				// 除个人中心外,附加地址信息
				if('address_list' != $link_from){
					$address = $res['data']; 
					$address = encrypt('EN', $address);
					$redirec_url = add_params($redirec_url, array(
						'address_id'	=>	$address['address_id'],
						'select_address'	=>	$address,
					));
				}
				echo_json(true, array(
						'message'	=>	'操作成功',
						'url'	=>	$redirec_url,
					) );
			}else{
				echo_json(false, '', array(
						'error_code'	=>	$res['error_code'],
						'message'	=>	$res['error'] ?: '操作失败，请稍后重试',
					) );
			}

		}	

		public function address_list(){
			global $_GPC,$_W;
			
			// 当前用户信息
			$user_id = $this->user_info['user_id'];
			$user_type = $this->user_info['user_type'];
			$client_id = $this->user_info['client_id'];
			$from_user = $this->user_info['from_user'];
		
			// 获取用户首页城市
			$user_city = get_user_city();
			$city_id = $user_city['city_id'];
			$city_name = $user_city['city_name'];
			
			// 当前用户地址列表
			$address_list = $this->open_server->get_address_list($user_id);
			$address_count = count($address_list);
			if($address_count){
				foreach($address_list as $k => $address){
					# 缓存地址列表，校验地址操作权限时读取
					redis()->sadd('address:' . $user_id, $address['address_id']);
					
					if($city_id == $address['city_id']){
						$address_list[$k]['sort_flag'] = 1;
					}else{
						$address_list[$k]['sort_flag'] = 0;
					}
				}
			}
			include $this->template('address_list' );
		}
		
		public function order_address_list(){
			global $_GPC,$_W;
			
			// 当前用户信息
			$user_id = $this->user_info['user_id'];
			$user_type = $this->user_info['user_type'];
			$client_id = $this->user_info['client_id'];
			$from_user = $this->user_info['from_user'];
			
			if(!empty($_GPC['tmp_order'])){
			    // 扫码下单ID
			    $tmp_order = $_GPC['tmp_order'];
			}
			if(!empty($_GPC['replace_order'])){
				$replace_order = $_GPC['replace_order'];
			}
			if(!empty($_GPC['category_id'])){
				$category_id = $_GPC['category_id'];
			}
			if(!empty($_GPC['address_id'])){
				$address_id = $_GPC['address_id'];
			}
			if(!empty($_GPC['comment'])){
				$comment = $_GPC['comment'];
			}
			// 从订单页跳过来时新加的
			if(!empty($_GPC['link_from'])){
				$link_from = $_GPC['link_from'];
			}
			if(!empty($_GPC['back'])){
				$back = $_GPC['back'];
				if($category_id){
					// 加上分类参数
					$back = urlencode(add_params(urldecode($back), array(
							'category_id'	=>	$category_id,
							'comment'	=>	$comment,
						)));
				}
			}
			// 获取用户首页城市
			if(!empty($_GPC['city_id'])){
				$city_id = $_GPC['city_id'];
			}else{
				$user_city = get_user_city();
				$city_id = $user_city['city_id'];
				$city_name = $user_city['city_name'];
			}
			// 当前用户地址列表
			if(is_numeric($category_id)){
				// 非普洗
				$address_list = $this->open_server->get_address_list($user_id, $category_id);
			}else if(is_array($category_id)){
				// 普洗,可以有多分类
				$address_list = $this->open_server->get_address_list($user_id, '-1', $category_id);
			}else{
				// 其他无category_id情况
				$address_list = $this->open_server->get_address_list($user_id);
			}
			$address_count = count($address_list);
			
			if(!$address_count){
				$params = array(
					'tmp_order' => $tmp_order,
					'category_id' => $category_id,
					'replace_order' => $replace_order,
					'link_from' => $link_from,
					'back' => $back,
					'sign' => 'create',
					);

				$add_addr_url = create_url('address/manage_address', $params);
				header('Location:' . $add_addr_url);
				exit;
			}
			
			// 返回订单页携带参数,构造返回下单URL

			if($link_from && $back){
				// 新的逻辑 返回url是$back
				$place_order_url = urldecode($back);
			}
			// 支持的地址
			$serv_list = array();
			// 同一城市不支持的地址
			$city_unserv_list = array();
			// 不同城市的地址
			$unserv_list = array();
			$query_data = array(
					'tmp_order' => $tmp_order,
					'category_id' => $category_id,
					'replace_order' => $replace_order,
					'link_from' => $link_from,
					'back' => $back,
					'sign' => 'create'
					);
			// 添加按钮url
			$create_address_url = create_url('address/manage_address', $query_data);
			
			if($address_count){
				// new
				foreach($address_list as $k => &$address){
					# 缓存地址列表，校验地址操作权限时读取
					redis()->sadd('address:' . $user_id, $address['address_id']);
					
					$address['can_wash'] = $address['can_wash'] ? 1 : 0;
					$query_data['sign'] = 'update';
   					$query_data['address_id'] = $address['address_id'];

					if($city_id == $address['city_id']){
						if ($address['can_wash']){
							$address['sort_flag'] = 1;
	       					$query_data['address'] = encrypt('EN', $address);
							$serv_list[$address['address_id']] = $address;
							$serv_list[$address['address_id']]['address_info'] = $query_data['address'];

							$serv_list[$address['address_id']]['manage_address_url'] = 
							create_url('address/manage_address', $query_data);
						}else{
							$address['sort_flag'] = 1;
	       					$query_data['address'] = encrypt('EN', $address);
							$city_unserv_list[$address['address_id']] = $address;
							$city_unserv_list[$address['address_id']]['address_info'] = $query_data['address'];

							$city_unserv_list[$address['address_id']]['manage_address_url'] = 
							create_url('address/manage_address', $query_data);
						}
						// encrypt('EN', $address);
					}else{
						$address['sort_flag'] = 0;
       					$query_data['address'] = encrypt('EN', $address);
						$unserv_list[$address['address_id']] = $address;
						$unserv_list[$address['address_id']]['address_info'] = $query_data['address'];
						$unserv_list[$address['address_id']]['manage_address_url'] = 
						create_url('address/manage_address', $query_data);
					}
				}
			}
			$serv_count = count($serv_list);
			$city_unserv_count = count($city_unserv_list);
			$unserv_count = count($unserv_list);
			$switch_city_tip = isset($_GPC['switch_city_tip']) ? 1 : 0;
			$un_service = isset($_GPC['un_service']) ? 1 : 0;
			include $this->template('order_address_list');
		}
				
		//ali_address
		public function ali_get_address(){
			global $_W,$_GPC;
	
			$address['address'] = $_GPC['address'];
			$address['username']  = $_GPC['username'];
			$address['tel']  = $_GPC['tel'];
			if(!empty($_GPC['washing_date'])){
				$washing_date = $_GPC['washing_date'];
				$washing_time = $_GPC['washing_time'];
				$time_range = $_GPC['time_range'];
			}
			$sign = "create";
			if(!empty($_GPC['link_from'])){
				$link_from = $_GPC['link_from'];
			}
			if(!empty($_GPC['comment'])){
				$comment = $_GPC['comment'];
			}/*
			$cities_options = $this->open_server->cities_options();
			$cities_options['cities'][] = '请选择城市';
			$cities_options['areas'][count($cities_options['areas'])][] = "请先选择城市";*/

			include $this->template('manage_address');
		}
	 	
		//验证地址是否在服务范围
		public function ajax_verify_address(){
			global $_W,$_GPC;
			$addrInfo = array(
				// 多品类传第一个
				'category_id' => explode(',', $_GPC['category_id'])[0],				//品类 区分快洗
				'city' => rtrim($_GPC['city'], '市'),							//城市名（字符串）   必填
				'area' => $_GPC['area'],							//区县名（字符串）   必填
			//	'address_line_1' => trim($_GPC['address_line_1']),	//定位地址名	      可选
			//	'customer_lng' => $_GPC['customer_lng'],  			//定位经度（默认0）  可选
			//	'customer_lat' => $_GPC['customer_lat'],   			//定位纬度（默认0）  可选
			//	'flag' => $_GPC['flag'],							//请求标识（整型）   可选
				'city_id' => $_GPC['city_id'],                      //城市ID    必填
				'area_id' => 0                                      //没有的话，填0  必填
			);
			$res = $this->open_server->verify_address($addrInfo);
			message($res, '', 'ajax');
		}
		

		// 生成“城市”下拉菜单选项列表
		function city_opts($open_data, $city_sel=''){
			$cites = array();
			foreach ($open_data as $v) {
				$cites[$v['city_id']] = $v['name'];
			}
			$opts = "<option class='default' value='' disabled='disabled'>请选择城市</option>";
			foreach($cites as $city_id => $city_name){
				if($city_sel && $city_name){
					$selected = $city_sel === $city_name ? "selected='selected'" : '';
				}else{
					$selected = '';
				}
				$opts .= "<option city_sn='city_$city_id' value='$city_name' $selected>$city_name</option>";
			}
			return $opts;
		}
		
		// 生成“区域”下拉菜单选项列表
		function area_opts($open_data, $city_sel, $area_sel='', $all=false){
			if(!$all){
				$opts = "<option class='default' value=''>请选择区域</option>";
			}
			foreach ($open_data as $item) {
				$city_id = $item['city_id'];
				foreach ($item['areas'] as $area) {
					$area_name = $area['name'];
					$area_id = $area['area_id'];
					if($area_sel && $area['name']){
						$selected = ($area_sel===$area['name']) ? "selected='selected'" : "";
					}else{
						$selected = "";
					}
					if($all){
						$opts .= "<option class='city_$city_id' value='$area_name' rel='$area_id' $selected>$area_name</option>";
					}else{
						if($city_sel === $item['name']){
							$opts .= "<option class='city_$city_id' value='$area_name' rel='$area_id' $selected>$area_name</option>";
						}
					}
				}
			}
			return $opts;
		}
		
	}
