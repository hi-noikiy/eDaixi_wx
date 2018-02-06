/**
 * 购物抛物线运动
 * @param {number} left  起点left
 * @param {number} top  起点top
 * @param {string} imgUrl 
 */
function addProduct(left, top, buynumber, price){
  var offset = $(".shopping-cart-wrap .shopping-cart img").offset(),
  flyer = $('<div class="fly-buy-number">' + buynumber + '</div>');
  flyer.fly({
    start: {
      left: left,
      top: top
    },
    end: {
      left: offset.left + 15,
      top: offset.top - $(window).scrollTop()
    },
    autoPlay: true,
    speed: 1.5,
    vertex_Rtop: 10,
    onEnd: function() {
        $(".fly-buy-number").remove();
        //购物车抖动
        $(".shopping-cart-wrap .shopping-cart img:first-child").animate({
            width: "4rem"
        }, { duration: 150, easing: "ease", complete: function(){
            $(".shopping-cart-wrap .shopping-cart img:first-child").animate({
                width: "3.32rem"
            }, {duration: 150, easing: "ease", complete: totalPrice(price)})
        }});
    }
  });
}

/**
 * 计算件数并计数价格
 * @param {string} price 每件物品的单价
 */
function totalPrice(price) {

    // 计算件数
    var totalNum = $(".shopping-cart-wrap .buy-number-total").text();
    if (!totalNum) {
        $(".shopping-cart-wrap .buy-number-total").text("1").show();
    }
    else {
        $(".shopping-cart-wrap .buy-number-total").text(+totalNum + 1);
    }

    // 计数总价
    var oldTotalPrice = $(".shopping-cart-wrap .shopping-cart .total-price").text();
    var newTotalPrice = ((+oldTotalPrice) + (+price)).toFixed(2);
    $(".shopping-cart-wrap .shopping-cart .total-price").text(newTotalPrice);
}

$(".prices-content-wrap, .taocan-wrap").on("click", ".price-box, .taocan-pic-cell, .curtain-content .small-bag, .curtain-content .big-bag, .small-bag-img, .big-bag-img", function(event) {

    // 显示购物车
    if (!$.contains($("footer").get(0), $(".shopping-cart-wrap").get(0))) {
        var shoppingCartHtml = template("shopping-cart", {});
        $("footer").html(shoppingCartHtml);
    }

    // 增加buynumber计数
    $(this).children(".buy-number").show();
    var price = $(this).find(".price").text();
    var number = $(this).children(".buy-number").text();
    if (!number) {
        $(this).children(".buy-number").text("1");
    }
    else {
        $(this).children(".buy-number").text(+number + 1);
    }
    var offset = $(this).children(".buy-number").offset();
    var left = offset.left;
    var top = offset.top - $(window).scrollTop();
    addProduct(left, top, +number+1, price);
    // 存储选中的衣物信息在sessionStorage中
    // 格式： {
    //      washing： [
    //         {
    //            id: xxxx,
    //        　　washing_name: "半身T恤b",
    //            image: "xxx",
    //            order: 1,
    //            price: 10,
    //            category_id: xxxx,   // 隶属的品类
    //            selected: true  // 预估价格时是否选中
    //         }
    //      ]
    // }
    var obj = {};
    obj.id = $(this).data("id");
    obj.category_id = $("header ul .active").data("id");
    obj.washing_name = $(this).find(".washing-name").text();
    if (+$(this).data("category-id") === 1070) {
        obj.washing_name = "按袋洗";
    }

    if ($(this).find(".pic-box img").length > 0) {
        obj.image = $(this).find(".pic-box img").prop("src");
    }
    if ($(this).find(".taocan-pic-cell img").length) {
        obj.image = $(this).find(".taocan-pic-cell img").prop("src");
    }
    // 洗窗帘图片
    if ($(this).find(".small-bag img").length) {
        obj.image = $(this).find(".small-bag img").prop("src");
    }
    if ($(this).find(".big-bag img").length) {
        obj.image = $(this).find(".big-bag img").prop("src");
    }
    // 按袋洗图片
    if ($(this).find(".small-bag-img img").length) {
        obj.image = $(this).find(".small-bag-img img").prop("src");
    }
    if ($(this).find(".big-bag-img img").length) {
        obj.image = $(this).find(".big-bag-img img").prop("src");
    } 

    obj.order = number ? (+number + 1) : 1;
    obj.price = price;
    obj.selected = true;

    var storage = window.sessionStorage;
    var productInfo = JSON.parse(storage.getItem("productInfo"));
    console.log(productInfo, "begin");
    if (productInfo) {
        var len = productInfo.washing.length;
        for (var i = 0; i < len; i++) {
            if (+obj.id === +productInfo.washing[i].id) {
                productInfo.washing[i] = obj;
                break;
            }
        }

        // 如果i等于len，表明判断一轮，此时需要添加
        if (i === len) {
             productInfo.washing.push(obj);
        }
    }
    else {
        var objTemp = {};
        objTemp.washing = [obj];
        productInfo =objTemp;
    }

    storage.setItem("productInfo", JSON.stringify(productInfo));
    console.log(productInfo, "end");
})