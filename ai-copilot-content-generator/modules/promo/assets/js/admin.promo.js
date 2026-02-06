"use strict";
(function ($, app) {
"use strict";
	function PromoClass() {
		this.$obj = this;
		return this.$obj;
	}
	
	PromoClass.prototype.init = function () {
		var _this = this.$obj;
		_this.isPro = WAIC_DATA.isPro == '1';
		_this.initDeactivationPopup();
		_this.initGuidePopup();
	}
	PromoClass.prototype.initDeactivationPopup = function () {
		var _this = this.$obj,
			$deactivateLnk = $('#the-list tr[data-plugin="' + WAIC_DATA.plugName + '"] .row-actions .deactivate a'),
			$deactivateLnkPro = WAIC_DATA.plugNamePro ? $('#the-list tr[data-plugin="' + WAIC_DATA.plugNamePro + '"] .row-actions .deactivate a') : false;
		if (($deactivateLnk && $deactivateLnk.length) || ($deactivateLnkPro && $deactivateLnkPro.length)) {
			_this.deactivationPopup = $('#waicDeactivationPopup');
			if (_this.deactivationPopup.length) {
				if ($deactivateLnk) {
					$deactivateLnk.on('click', function() {
						_this.deactivationPopup.addClass('waic-popup-show').attr('data-plugin', 'free');
						return false;
					});
				}
				if ($deactivateLnkPro) {
					$deactivateLnkPro.on('click', function() {
						_this.deactivationPopup.addClass('waic-popup-show').attr('data-plugin', 'pro');;
						return false;
					});
				}
				_this.deactivationPopup.on('click', function(e) {
					if ($(e.target).hasClass('waic-admin-popup')) _this.deactivationPopup.removeClass('waic-popup-show');
				});
				this.deactivationPopup.find('.waic-popup-close, button.waic-cancel').on('click', function(e) {
					_this.deactivationPopup.removeClass('waic-popup-show');
				});
				this.deactivationPopup.find('button.waic-skip, button.waic-deactivate').on('click', function(e) {
					var isSkip = $(this).hasClass('waic-skip'),
						plugin = _this.deactivationPopup.attr('data-plugin');
					$.sendFormWaic({
						data: {
							mod: 'promo',
							action: 'sendDeactivationReason',
							skip: isSkip ? 1 : 0,
							plugin: plugin,
							reason: _this.deactivationPopup.find('input[name="waic_reason"]:checked').val(),
							other: _this.deactivationPopup.find('textarea[name="waic_other"]').val()
						},
						onSuccess: function(res) {
							if (plugin == 'pro') toeRedirect($deactivateLnkPro.attr('href'));
							else toeRedirect($deactivateLnk.attr('href'));
						}
					});
					_this.deactivationPopup.find('.waic-popup-panel').html('<div class="waic-loader"><div class="waic-loader-bar bar1"></div><div class="waic-loader-bar bar2"></div></div>');
				});
			}
		}
	}
	PromoClass.prototype.initGuidePopup = function () {
		var _this = this.$obj;
		_this.guidePopup = $('#waicGuidePopup');
		if (_this.guidePopup.length) {
			_this.guidePopup.removeAttr('style');
			setTimeout(function() {
				_this.guidePopup.addClass('waic-popup-show');
			}, 2000);
			
			this.guidePopup.find('.waic-popup-close, button').on('click', function(e) {
				_this.guidePopup.removeClass('waic-popup-show');
				var action = $(this).attr('data-action');
				if (action) _this.getGuideStep(action);
			});
			$('.waic-start-guide').on('click', function(e) {
				if (_this.guidePopup.hasClass('waic-popup-hidden')) {
					_this.getGuideStep('start');
				}
			});
		}
	}
	PromoClass.prototype.getGuideStep = function (action) {
		var _this = this.$obj;
		$.sendFormWaic({
			data: {
				mod: 'promo',
				action: action + 'Guide',
				step: _this.guidePopup.attr('data-step'),
			},
			onSuccess: function(res) {
				if (res && res.data) {
					if (res.data.is_end) {
						$('.waic-start-guide').addClass('waic-hidden');
					} else if (res.data.is_skip) {
						_this.guidePopup.addClass('waic-popup-hidden');
					} else {
						var redirect = res.data.url;
						if (typeof(redirect) == 'undefined' || !redirect || redirect == window.location.href) {
							var step = res.data.step;
							if (typeof(step) != 'undefined' && step >= 0) {
								_this.guidePopup.attr('data-step', step);
								_this.guidePopup.find('.waic-popup-header .waic-text').html((step + 1) + '. ' + res.data.title);
								_this.guidePopup.find('.waic-popup-body .waic-popup-block').html(res.data.body);
								_this.guidePopup.find('.waic-popup-button').addClass('waic-popup-hidden');
								var btns = ['next', 'back', 'skip', 'finish'];
								for (var i = 0; i < btns.length; i++) {
									if (res.data[btns[i]]) _this.guidePopup.find('.waic-popup-button[data-action="' + btns[i]+ '"]').removeClass('waic-popup-hidden');
								}
								_this.guidePopup.addClass('waic-popup-show').removeClass('waic-popup-hidden');
							}
						} else toeRedirect(redirect);
					}
				}
			}
		});
	}
	app.waicPromoClass = new PromoClass();

	$(document).ready(function () {
		app.waicPromoClass.init();
	});

}(window.jQuery, window));
