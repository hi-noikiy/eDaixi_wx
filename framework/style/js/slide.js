$(function(){var alw = 20,cuurmtop = 0,tStart = 0,tEnd = 0,start2end = 0,currScreen = 1,currHeight = $(window).height();var playAni = function(){}
var slide = function(){$("#wrap ul").css({"margin-top":-currHeight*(currScreen-1)}
);var callback = arguments[0];if($.isFunction(callback)) setTimeout(callback,500)}
$("#wrap").on("touchstart",function(e){e.preventDefault();tStart = e.targetTouches[0].clientY}
).on("touchmove",function(e){tEnd = e.targetTouches[0].clientY;start2end = tEnd - tStart;if((currScreen==1 && start2end>=0) || (currScreen==4 && start2end<=0)) return;$("#wrap ul").removeClass("active").css({"margin-top":Number(cuurmtop)+start2end}
)}).on("touchend",function(e){if(start2end){$("#wrap ul").addClass("active");var isChange = true;if(start2end+alw<0){isChange = currScreen++>=4;currScreen = isChange ? currScreen-1:currScreen}
if(start2end-alw>0){isChange = currScreen--<=1;currScreen = isChange ? currScreen+1:currScreen}
slide(!isChange?playAni:undefined);cuurmtop = -currHeight*(currScreen-1);tStart = tEnd = start2end = 0}
})
  
  var init = function(){currHeight = $(window).height();$("#wrap").height(currHeight);slide(playAni)}
var docSt = setInterval(function(){if(document.readyState=="complete"){clearInterval(docSt);$(window).resize(init).triggerHandler("resize")}
},10)}
)