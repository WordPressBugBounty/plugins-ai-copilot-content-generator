(function ($, app) {
"use strict";
	function GoProPage() {
		this.$obj = this;
		return this.$obj;
	}
	
	GoProPage.prototype.init = function () {
		var _this = this.$obj;
		_this.priceForm = $('.waic-price-form');
		_this.contactForm = $('.waic-contact-form');
		
		if (_this.priceForm.length) _this.eventsGoProPrice();
		if (_this.contactForm.length) _this.eventsGoProContact();

		
	}
	GoProPage.prototype.eventsGoProPrice = function () {
		var _this = this.$obj;
		_this.priceButton = _this.priceForm.find('#waicPurchaseBtn');
		_this.priceForm.find('input[name="waicPlan"]').on('change', function() {
			var $this = $(this);
			_this.priceButton.attr('href', $this.attr('data-url'));
			_this.priceForm.find('.waic-price-column').removeClass('active');
			$(this).closest('.waic-price-column').addClass('active');
		});
		
	}
	GoProPage.prototype.eventsGoProContact = function () {
		var _this = this.$obj;
		_this.contactButton = _this.contactForm.find('#waicSend');
		_this.contactButton.on('click', function(e){
			e.preventDefault();

			$.sendFormWaic({
				elem: $(this),
				data: {
					mod: 'promo',
					action: 'contactForm',
					email: _this.contactForm.find('input[name="waicEmail"').val(),
					name: _this.contactForm.find('input[name="waicName"').val(),
					subject: _this.contactForm.find('input[name="waicSubject"').val(),
					body: _this.contactForm.find('textarea[name="waicBody"').val(),
				},
				onSuccess: function(res) {
					if (!res.error) {
						_this.contactForm.find('input, textarea').val('');
					}
				}
			});
			return false;
		});
	}
	
	app.aiwuGoPro = new GoProPage();

	$(document).ready(function () {
		app.aiwuGoPro.init();
	});

}(window.jQuery, window));