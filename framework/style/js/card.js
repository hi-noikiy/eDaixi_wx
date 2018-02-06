// JavaScript Document
// 展开与收缩
function navList(id) {
    var $obj = $(".navlist");
    $obj.find(".list-titel").click(function () {
        var $div = $(this).siblings(".list-item");
        if ($(this).parent().hasClass("selected")) {
            $div.slideUp(600);
            $(this).parent().removeClass("selected");
        }
        if ($div.is(":hidden")) {
            $("#J_navlist li").find(".list-item").slideUp(600);
            $("#J_navlist li").removeClass("selected");
            $(this).parent().addClass("selected");
            $div.slideDown(400);
        } else {
            $div.slideUp(400);
        }
    });  

    $obj.find(".btn_cancle").click(function(){
    	$obj.find("li").removeClass("selected");
    	$(".list-item").hide();
    });
}