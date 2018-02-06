// JavaScript Document
/*幻灯片*/

slider = function(time) {
  var spans = "";
  var T = 0;
  var timeout = time;
  for (var i = 0; i < $(".swipe-wrap div").length; i++) {
    spans += "<span></span>";
  }
  var setIntervals = function() {
    if (T < $(".swipe-wrap div").length) {
      T++;
    } else {
      T = 0;
    };
    return $("#sbsd span").eq(T).trigger("click");
  };

  var setauto = setInterval(setIntervals, timeout);
  $(".swipe-wrap div").hover(function() {
    clearInterval(setauto);
  }, function() {
    setauto = setInterval(setIntervals, timeout);
  });
  $("#sbsd").html(spans);
  $("#sbsd span").eq(0).addClass("on");
  $("#sbsd span").bind('click', function() {
    var numb = $("#sbsd span").index(this);
    slider.slide(numb);
  });
  var slider = Swipe(document.getElementById('slider'), {
    continuous: true,
    callback: function(pos) {
      var i = $("#sbsd span").length;
      while (i--) {
        $("#sbsd span").removeClass("selects");
      }
      $("#sbsd span").eq(pos).addClass("on").siblings("span").removeClass("on");
    }
  });

  if (time === undefined) {
    clearInterval(setauto);
  }
};
/*幻灯结束*/

// JavaScript Document
/*幻灯片*/

commentslist = function(time) {
  
  var T = 0;
  var timeout = time;
  
  var setIntervals = function() {
    if (T < $(".comment-block div.box").length) {
      T++;
    } else {
      T = 0;
    }
    
  };

  var setauto = setInterval(setIntervals, timeout);
  $(".comment-block div.box").hover(function() {
    clearInterval(setauto);
  }, function() {
    setauto = setInterval(setIntervals, timeout);
  });


  var commentslist = Swipe(document.getElementById('comments-list'), {
    continuous: true,
    callback: function(pos) {

    }
  });

  if (time === undefined) {
    clearInterval(setauto);
  }
};
/*幻灯结束*/

// lottery轮播
/*幻灯片*/

lotterySlider = function(time) {
  var spans = "";
  var T = 0;
  var timeout = time;
  for (var i = 0; i < $(".lottery-wrap .lottery").length; i++) {
    spans += "<span></span>";
  }
  var T = 0;
  var timeout = time;
  
  var setIntervals = function() {
    if (T < $(".lottery-wrap .lottery").length) {
      T++;
    } else {
      T = 0;
    }
    //轮播
    return $("#sbsd span").eq(T).trigger("click");
  };

  var setauto = setInterval(setIntervals, timeout);
  $(".lottery-wrap .lottery").hover(function() {
    clearInterval(setauto);
  }, function() {
    setauto = setInterval(setIntervals, timeout);
  });

  $("#sbsd").html(spans);
  $("#sbsd span").eq(0).addClass("on");
  $("#sbsd span").bind('click', function() {
    var numb = $("#sbsd span").index(this);
    lotterySlider.slide(numb);
  });
  var lotterySlider = Swipe(document.getElementById('lotterySlider'), {
    continuous: true,
    callback: function(pos) {
      var i = $("#sbsd span").length;
      while (i--) {
        $("#sbsd span").removeClass("selects");
      }
      $("#sbsd span").eq(pos).addClass("on").siblings("span").removeClass("on");
    }
  });

  if (time === undefined) {
    clearInterval(setauto);
  }
};
/*幻灯结束*/

