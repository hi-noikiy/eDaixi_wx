/**
 * jQuery 模拟淘宝控件银行帐号输入
 */
(function($) {
	// 输入框格式化
	$.fn.bankInput = function(options) {
		var defaults = {
			max : 25, 			// 最多输入字数
			deimiter : ' ', 	// 账号分隔符
			onlyNumber : true, 	// 只能输入数字
			copy : true			// 允许复制
		};
		var opts = $.extend({}, defaults, options);
		var el = $(this);
		var maxLen = parseInt(opts.max) + Math.floor(parseInt(opts.max) / 4);
		el.css({imeMode : 'Disabled'}).attr('maxlength', maxLen);
		if (el.val() != ''){
			el.val(el.val().replace(/\s/g, '').replace(/(\d{4})(?=\d)/g, "$1" + opts.deimiter));
		}
		el.on('keyup', function(event) {
			if (opts.onlyNumber) {
				if (!(event.keyCode >= 48 && event.keyCode <= 57)) {
					this.value = this.value.replace(/\D/g, '');
				}
			}
			this.value = this.value.replace(/\s/g, '').replace(/(\d{4})(?=\d)/g, "$1" + opts.deimiter);
		}).on('dragenter', function() {
			return false;
		}).on('onpaste', function() {
			return !clipboardData.getData('text').match(/\D/);
		});
	}
	// 列表显示格式化
	$.fn.bankList = function(options) {
		var defaults = {
			deimiter : ' ' // 分隔符 
		};
		var opts = $.extend({}, defaults, options);
		return this.each(function() {
			$(this).text($(this).text().replace(/\s/g, '').replace(/(\d{4})(?=\d)/g, "$1" + opts.deimiter));
		})
	}
})(jQuery);