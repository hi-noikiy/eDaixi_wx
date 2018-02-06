一、下单页有多个入口进入， 可以通过价目页，地址列表页和时间控件页面进入。这个三个页面在互相跳转时需要通过URL传递参数，其规则如下：
 
1. 价目页到下单页，需要传：
   category_id, price_read, city_id. 这三个参数是获取下单页json是需要的。
   mark (渠道，从首页获取的，如果有就要传)

2. 下单页到地址列表页，需要传：
   
   category_id (用户会修改所选品类)， address_id (如果有默认地址的话，在地址列表页会勾选）

3. 地址列表页到下单页：
    category_id, price_read, city_id ,(这三个参数在之前进入下单页时读取json是作为参数传给后端，并且被记录)
      address_id, select_address(格式类似default_address,其中的address_id有重复)

4. 下单页到时间控件页， 有两种情况（通过判断url是否包含select_address）：
    a. 地址栏使用default_address 时：
       category_id, city_id, price_read, (这三个参数要回传)
       area， area_id 
    b. 地址栏使用的是修改过的地址：
       category_id, city_id, price_read, area, area_id,
       address_id, select_address (这两个参数要回传)
5. 时间控件页到下单页，有两种情况：
    a. category_id, city_id, price_read .（这三个参数要回传，已读取下单页的json）
       washing_date, washing_time, time_range (这三个参数是用于生产下单提交时使用)
    b. 如果除了category_id, city_id, price_read 外，还有address_id和select_address的话也要回传，用来填充地址栏。  washing_date, washing_time, time_rang (这三个参数是用于生产下单提交时使用)
       
     
二、 进入写字楼快洗的下单页有三个渠道，价目页，写字楼搜索页和时间控件页
     1. 从写字楼搜索页面跳转到下单页时，通过url传office_building_id=9&office_name=神经&office_area=东城区， 其中office_name直接显示在写字楼地址栏中。
三、 价目页分为app（ios和android）运行环境和微信运行环境
     通过UA中是否包含”edaixi_app“来判断是否app环境

     1. 微信环境： 价目页向php端请求数据。
     2. app环境： 直接向ruby端请求数据。 请求数据时先调用native的handle获取参数，这些参数放在请求ruby接口的head部分，在请求时发送给ruby端。

                  在点击“预约取件”时是调用loginHandle 进入登录页（原生）还是调用shoppingHandle 进入下单页（原生），可以通过sessionId（具体名字:open_session_id）来判断。
                  sessionId过期问题： ruby返回{ret：false; error_code: 40001 }, 此时需要调用app的方法重新获得sessionid并刷新页面。

