
//应用
$(function() {
	showPic();//焦点图切换
});
function showPic(){
    var _lis =$(".picCont li"),
        _ol = $(".showPic ol"),
        liLen = _lis.length-1,
        liW = _lis.width(),
        tempArr = [],
        curIdx=0,
        timer;
    (function init(){
        $(".picCont li:first").clone().appendTo(_ol);
        for(var i =0; i < liLen+1 ;i++){
            tempArr.push(i+1);
        }
        $(".Num").html("<li>"+tempArr.join("<\/li><li>")+"<\/li>");
    })();
    var numLi = $(".Num li");
    cur(numLi.eq(curIdx));
    
    $(".showPic").hover(function(){
            clearInterval(timer);
        },function(){
            timer = setInterval(autoImg,4000);
    }).trigger("mouseleave");
    
    numLi.mouseover(function(){
        curIdx = $(this).index();
        move(curIdx);
    });

    function autoImg(){
        var idx = $(".Num").find("li.cur").index();
        idx = (idx==liLen) ?0 : idx+1;
        move(idx);
    }
    
    function move(i){
		if(!_ol.find("li:first").is(":animated")){
        cur(numLi.eq(i));
        _lis.eq(i).clone().appendTo(_ol);
        _ol.find("li:first").animate({"margin-left":-liW},300,function(){
            $(this).remove();
        });}
    }
    
    function cur(ele,currentClass,tag){
        var ele= $(ele) || ele,
            tag= tag ||"",
            mark= currentClass ||"cur";
        ele.addClass(mark).siblings(tag).removeClass(mark);
    }
    
}