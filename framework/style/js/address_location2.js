var geolocation, mGeocoder, placeSearch, jqueryXhr = null,
timer, errFlag = 0;
function autoPostion() {
    waiting("正在定位  ");
    if (AMap) {
        AMap.plugin("AMap.Geolocation",
        function() {
            geolocation = new AMap.Geolocation({
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0,
                convert: true
            });
            geolocation.getCurrentPosition();
            AMap.event.addListener(geolocation, "error",
            function(b) {
                var a = "获取数据失败";
                switch (b.info) {
                case "PERMISSION_DENIED":
                    a += "，浏览器阻止了定位操作";
                    break;
                case "POSITION_UNAVAILBLE":
                    a += "，无法获得当前位置";
                    break;
                case "TIMEOUT":
                    a += "，定位超时";
                    break;
                default:
                    a += "，请检查GPS或网络连接是否正常";
                    break
                }
                stopLocate(a)
            });
            AMap.event.addListener(geolocation, "complete",
            function(b) {
                if (b.accuracy) {
                    var a = b.position.getLng();
                    var c = b.position.getLat();
                    getLocateInfo(a, c, true)
                } else {
                    stopLocate()
                }
            })
        })
    } else {
        stopLocate("暂无定位数据，请手动填写地址")
    }
}
function getLocateInfo(a, c, d) {
    if (AMap) {
        var b = new AMap.LngLat(a, c);
        AMap.service(["AMap.Geocoder"],
        function() {
            mGeocoder = new AMap.Geocoder({
                radius: 100,
                extensions: "base"
            });
            mGeocoder.getAddress(b,
            function(g, e) {
                if (g === "complete" && e.info === "OK") {
                    var j = e.regeocode.formattedAddress;
                    var f = e.regeocode.addressComponent;
                    var k = j.replace(new RegExp(f.province + f.city + f.district), "").replace(f.streetNumber, "");
                    var h = "";
                    if (f.township) {
                        h += f.township
                    }
                    if (f.street) {
                        h += f.street
                    }
                    if (f.streetNumber) {
                        h += f.streetNumber.replace(/号/, "") + "号"
                    }
                    var i = {
                        addrName: k,
                        city: f.city || f.province,
                        citycode: f.citycode,
                        district: f.district,
                        addrDetail: h,
                        lng: a,
                        lat: c
                    };
                    if (d) {
                        verifyAddress(i)
                    } else {
                        return i
                    }
                } else {
                    stopLocate()
                }
            })
        })
    } else {
        stopLocate("暂无定位数据，请手动填写地址")
    }
}
function autoComplete(a, b) {
    var b = $.trim(b);
    if (!b || !a) {
        hideSearchList();
        hideUserWin();
        return false
    } else {
        if (AMap) {
            AMap.service(["AMap.PlaceSearch"],
            function() {
                placeSearch = new AMap.PlaceSearch({
                    city: a,
                    pageSize: 6,
                    extensions: "all"
                });
                placeSearch.search(b,
                function(h, r) {
                    var e = [];
                    if (h == "complete") {
                        var m = r.poiList.count;
                        m = m > 6 ? 6 : m;
                        if (m > 0) {
                            var f = "";
                            for (var k = 0; k < m; k++) {
                                var d = r.poiList.pois[k];
                                if (d.cityname.indexOf(a) > -1||d.adname.indexOf(a) > -1) {
                                    e[k] = {
                                        addrName: d.name,
                                        city: d.cityname,
                                        citycode: d.citycode,
                                        district: d.adname,
                                        addrDetail: d.address,
                                        lng: d.location.lng,
                                        lat: d.location.lat
                                    };
                                    var o = '"' + e[k].addrName + '"',
                                    g = '"' + e[k].city + '"',
                                    j = '"' + e[k].citycode + '"',
                                    q = '"' + e[k].district + '"',
                                    l = '"' + e[k].addrDetail + '"',
                                    p = '"' + e[k].lng + '"',
                                    n = '"' + e[k].lat + '"',
                                    c = e[k].city + " " + e[k].district + " " + e[k].addrDetail;
                                    f += "<div onclick='selectSearchList(" + o + "," + g + "," + j + "," + q + "," + l + "," + p + "," + n + ");' style='cursor:pointer;'>" + e[k].addrName + "<br/><span style='color:#999;font-size:0.9em'>" + c + "</span></div><div class='borderD'></div>"
                                }
                            }
                            if (f.length) {
                                f += "<div onclick='showUserWin();' style='text-align:center;color:#00dbf5;margin-left:0'>没有想要的地址，尝试手动选择市、区</div>";
                                showSearchList(f)
                            } else {
                                showUserWin()
                            }
                        } else {
                            showUserWin()
                        }
                    } else {
                        showUserWin()
                    }
                })
            })
        } else {
            showUserWin()
        }
    }
}
function waiting(a) {
    $("#wait_msg").text(a);
    $(".waiting").show();
    setTimeout(function() {
        $(".waiting").on("click",
        function() {
            stopLocate()
        })
    },
    2000)
}
function locateFinish() {
    $(".waiting").hide();
    $("#location_btn").one("click",
    function() {
        autoPostion()
    })
}
function stopLocate() {
    $(".waiting").hide();
    var a = arguments[0] ? arguments[0] : "请检查网络连接，开启GPS";
    showError(a, true)
}
function showMainWin() {
    $("#searchList").empty();
    $("#searchWin").hide();
    $("#userWin").hide();
    $("#mainWin").show()
}
function showSearch() {
    var b = $.trim($("#addr_name").val());
    if (b) {
        if (SCFlag == 1) {
            var a = $("#home_city_sel option:selected").val()
        } else {
            var a = $("#cmbCity option:selected").val()
        }
        autoComplete(a, b);
        $("#searchInp_clear").show()
    } else {
        $("#searchInp_clear").hide()
    }
    $("#mainWin").hide();
    $("#userWin").hide();
    $("#searchWin").show();
    $("#searchInp").focus().val(b);
    xbtnStatus($("#searchInp")[0])
}
function showSearchList(a) {
    $("#userWin").hide();
    $("#searchList").html(a).show()
}
function showUserWin() {
    if ($("#userWin:visible").length == 0) {
        if (SCFlag == 1) {
            var a = $("#home_city_sel option:selected").val();
            var b = ""
        } else {
            var a = $("#cmbCity option:selected").val();
            var b = $("#cmbArea option:selected").val()
        }
        selectedCity2(a, b)
    }
    setBtnStatus2();
    $("#searchList").empty().hide();
    $("#userWin").show();
    $("#searchInp").focus();
    xbtnStatus($("#searchInp")[0])
}
function hideSearchList() {
    $("#searchList").empty().hide()
}
function hideUserWin() {
    $("#userWin").hide()
}
function autoSearch(c) {
    var b = $.trim($(c).val());
    if (!b) {
        hideSearchList();
        hideUserWin();
        return false
    } else {
        if (SCFlag == 1) {
            var a = $("#home_city_sel option:selected").val()
        } else {
            var a = $("#cmbCity option:selected").val()
        }
        autoComplete(a, b)
    }
}
function selectSearchList(h, g, f, e, b, a, d) {
    var c = {
        addrName: h,
        city: g,
        citycode: f,
        district: e,
        addrDetail: b,
        lng: a,
        lat: d
    };
    verifyAddress(c)
}
function verifyAddress(d) {
    var c = 1,
    b = 127,
    a = Math.floor(Math.random() * (b - c + 1) + c);
    if (jqueryXhr) {
        jqueryXhr.abort()
    }
    jqueryXhr = $.ajax({
        url: verifyUrl,
        type: "POST",
        async: true,
        timeout: 2000,
        data: {
            category_id: category_id,
            city: d.city,
            area: d.district,
            address_line_1: d.addrName,
            customer_lng: d.lng,
            customer_lat: d.lat,
            flag: a
        },
        dataType: "json",
        complete: function(f, e) {
            locateFinish();
            jqueryXhr = null
        },
        success: function(f, h, j) {
            if (f.message.data) {
                var g = f.message.data;
                var e = g.on_service;
                if (e) {
                    selectedCity(d.city, d.district);
                    // selectedCity("金华市", "永康市");
                    // selectedCity("北京市", "朝阳区");
                    returnSearchAddr(d)
                }
                if (!e) {
                    var i = g.message;
                    resetLngLat();
                    $("#addr_name").val("");
                    showError(i)
                }
            } else {
                selectedCity(d.city, d.district);
                returnSearchAddr(d)
            }
        },
        error: function(g, f, e) {
            selectedCity(d.city, d.district);
            returnSearchAddr(d)
        }
    })
}
function clearKeyword() {
    $("#searchInp").val("");
    $("#searchList").empty()
}
function returnSearchAddr(a) {
    $(":hidden[name='customer_lng']").val(a.lng);
    $(":hidden[name='customer_lat']").val(a.lat);
    $("#addr_name").val(a.addrName);
    $("#home_city").hide();
    $("#cmbArea_wrap").show();
    SCFlag = 2;
    $("#select_wrap").show();
    setSaveBtn();
    showMainWin()
}
function resetLngLat() {
    $(":hidden[name='customer_lng']").val(0);
    $(":hidden[name='customer_lat']").val(0)
}
function showError(b) {
    if (errFlag) {
        return
    }
    var a = arguments[1] ? true: false;
    if (a) {
        errFlag = 1
    }
    $("#show_mes").html(b);
    $("#codFloat").show();
    setTimeout(function() {
        $("#show_mes").html("");
        $("#codFloat").hide();
        if (a) {
            var c = window.location.href;
            window.location.href = c.replace(/&pos=no/, "") + "&pos=no"
        }
    },
    2000)
}
function checkform() {
    var h = $.trim($("#username").val());
    var d = $(":radio[name='gender']:checked").length;
    var b = $.trim($("#tel").val());
    var f = $.trim($("#addr_name").val());
    var g = $("#cmbCity option:selected").val();
    var e = $("#cmbArea option:selected").val();
    var a = $.trim($("#detail").val());
    if (h == "") {
        showError("姓名不能为空");
        return false
    }
    if (d < 1) {
        showError("您是女士还是先生呢？");
        return false
    }
    var c = /^1[3-8]\d{9}$/;
    if (b == "") {
        showError("手机号不能为空");
        return false
    } else {
        if (!c.test(b)) {
            showError("请正确填写手机号码");
            return false
        }
    }
    if (!g || g == "" || g == "请选择城市") {
        showError("城市不能为空");
        return false
    }
    if (f == "") {
        showError("小区名或者大厦名不能为空");
        return false
    }
    if (!e || e == "" || e == "请先选择区域") {
        showError("区域不能为空");
        return false
    }
    if (a == "") {
        showError("详细地址不能为空");
        return false
    }
    $("#area_id").val($("#cmbArea").find("option:selected").attr("rel"));
    $("#save_address").prop("disabled", true).css({
        "opacity": ".5"
    })
}
function xbtnStatus(c) {
    var b = $(c).val();
    var a = $(c).attr("id") + "_clear";
    if ($.trim(b).length > 0) {
        $("#" + a).show()
    } else {
        $("#" + a).hide()
    }
}
function clearInput(a, b) {
    $("#" + b).val("");
    $(a).hide();
    if ("searchInp" == b) {
        $("#searchList").empty();
        $("#confirm_address").prop("disabled", true).css({
            "opacity": ".5"
        });
        $("#userWin").hide()
    }
    if ("tel" == b || "username" == b || "addr_name" == b || "detail" == b) {
        $("#save_address").prop("disabled", true).css({
            "opacity": ".5"
        })
    }
}
function setSaveBtn() {
    var e = $.trim($("#username").val());
    var h = $(":radio[name='gender']:checked").length;
    var d = $.trim($("#tel").val());
    var a = $.trim($("#addr_name").val());
    var c = $("#cmbCity option:selected").val();
    var b = $("#cmbArea option:selected").val();
    var g = $.trim($("#detail").val());
    var f = true;
    var i = /^1[3-8]\d{9}$/;
    if (d == "" || !i.test(d)) {
        f = false
    }
    if (e && h && f && a && c && b && g) {
        $("#save_address").prop("disabled", false).css({
            "background": "#00dbf5"
        });
        return true
    } else {
        $("#save_address").prop("disabled", true).css({
            "background": "#ccc"
        });
        return false
    }
}
function selectedCity(f, c) {
    var flag = false;
    if (f) {
        f = f.replace(/市/, "");
        var e = $("#cmbCity").find("option[value='" + f + "']");
        e.prop("selected", true);
        var b = e.attr("city_sn");
        if (!b) {            
          c = c.replace(/市/, "");
          e = $("#cmbCity").find("option[value='" + c + "']");
          e.prop("selected", true);
          b = e.attr("city_sn");
          if (b) {
            flag = true;
          }
        }

        var a = $("#area_options_list option." + b).clone(true);
        $("#cmbArea option:not(.default)").remove();
        $("#cmbArea").append(a);
        if (c && !flag) {
            var d = $("#cmbArea").find("option[value^='" + c + "']");
            d.prop("selected", true)
        } else {
            $("#cmbArea option.default").prop("selected", true)
        }
    }
    resetLngLat();
    
}
function selectedCity2(f, c) {
    if (f) {
        var e = $("#user_city").find("option[value='" + f + "']");
        e.prop("selected", true);
        var b = e.attr("city_sn");
        var a = $("#area_options_list option." + b).clone(true);
        $("#user_area option:not(.default)").remove();
        $("#user_area").append(a);
        if (c) {
            var d = $("#user_area").find("option[value^='" + c + "']");
            d.prop("selected", true)
        } else {
            $("#user_area option.default").prop("selected", true)
        }
    }
}
function returnKeyword2() {
    var b = $.trim($("#searchInp").val());
    var a = $("#user_city option:selected").val();
    var f = $("#user_area option:selected").val();
    var d = $.trim($("#addr_name").val());
    var e = $("#cmbCity option:selected").val();
    var c = $("#cmbArea option:selected").val();
    if (b != d || a != e || f != c) {
        resetLngLat();
        selectedCity(a, f);
        $("#addr_name").val(b)
    }
    $("#home_city").hide();
    $("#cmbArea_wrap").show();
    SCFlag = 2;
    $("#select_wrap").show();
    setSaveBtn();
    showMainWin()
}
function setBtnStatus2() {
    var a = $("#user_city option:selected").val();
    var c = $("#user_area option:selected").val();
    var b = $.trim($("#searchInp").val());
    if (a && c && b) {
        $("#confirm_address").prop("disabled", false).css({
            "opacity": "1"
        });
        return true
    } else {
        $("#confirm_address").prop("disabled", true).css({
            "opacity": ".5"
        });
        return false
    }
};