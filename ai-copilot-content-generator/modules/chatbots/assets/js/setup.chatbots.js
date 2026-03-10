(function ($, app) {
"use strict";
	function ChatbotSetupPage() {
		this.$obj = this;
		return this.$obj;
	}
	
	ChatbotSetupPage.prototype.init = function () {
		var _this = this.$obj;
		_this.isPro = WAIC_DATA.isPro == '1';

		//_this.langSettings = waicParseJSON($('#waicLangSettingsJson').val());
		_this.content = $('.waic-body-setup');
		_this.createForm = _this.content.find('#waicChatbotCreateForm');
		_this.engineInput = _this.content.find('#waicAIEngine');
		_this.apiKeys = _this.content.find('#waicApiKeys');
		_this.waitingPopup = $('#waicWaitingPopup');
		_this.progressBar = _this.waitingPopup.find('.waic-progressbar span');
		_this.percentsText = _this.waitingPopup.find('.waic-waiting-percents');
		//_this.generationEtaps = ['summary', 'prompt', 'localize'];
		_this.generationFlag = false;

		_this.schemeColor = _this.content.find('#waicSchemeColor');
		
		_this.eventsChatbotSetupPage();
	}
	
	ChatbotSetupPage.prototype.eventsChatbotSetupPage = function () {
		var _this = this.$obj;
		
		_this.content.find('.waic-settings-gallery').on('click', '.waic-gallery-element:not(.selected)',function(e){
			e.preventDefault();
			var $this = $(this),
				$elem = $this.closest('.waic-gallery-element'),
				$file = $elem.attr('data-file');
			$this.closest('.waic-settings-gallery').find('.waic-gallery-element').removeClass('selected');
			$elem.addClass('selected');
			if ($file == '') {
				$file = $elem.find('img').attr('src');
			}
			$this.closest('.waic-media-wrap').find('input').val($file).trigger('change');
			return false;
		});
		_this.content.find('.waic-media-delete').on('click', function(e){
			e.preventDefault();
			var $this = $(this),
				$wrap = $this.closest('.waic-settings-gallery');
			$this.closest('.waic-gallery-element').addClass('wbw-hidden');
			$wrap.find('.waic-gallery-upload').removeClass('wbw-hidden');
			$wrap.find('.waic-gallery-element:not(.waic-gallery-media)').eq(0).trigger('click');
			return false;
		});
		
		_this.content.find('.waic-gallery-upload').off('click').on('click', function (e) {
			e.preventDefault();
			var $button = $(this),
				$wrap = $button.closest('.waic-media-wrap'), 
				//$input = $wrap.find('input'),
				$preview = $wrap.find('.waic-custom-media'),
				_custom_media = true;
			wp.media.editor.send.attachment = function (props, attachment) {
				wp.media.editor._attachSent = true;
				if (_custom_media) {
					var selectedUrl = attachment.url;
					if (props && props.size && attachment.sizes && attachment.sizes[props.size] && attachment.sizes[props.size].url) {
						var imgSize = attachment.sizes[props.size];
						selectedUrl = imgSize.url;
					}
					//$input.val(selectedUrl);
					if ($preview.length) {
						$preview.attr('src', selectedUrl).parent().removeClass('wbw-hidden').trigger('click');
						$button.addClass('wbw-hidden');
					}
				} else return _orig_send_attachment.apply(this, [props, attachment]);
			};
			wp.media.editor.insert = function (html) {
				if (_custom_media) {
					if (wp.media.editor._attachSent) {
						wp.media.editor._attachSent = false;
						return;
					}
					if (html && html != "") {
						var selectedUrl = $(html).attr('src');
						if (selectedUrl) {
							//$input.val(selectedUrl);
							if ($preview.length) {
								$preview.attr('src', selectedUrl).parent().removeClass('wbw-hidden').trigger('click');
								$button.addClass('wbw-hidden');
							}
						}
					}
				}
			};
			wp.media.editor.open($button);
			return false;
		});
		
		_this.content.find('.waic-setup-check').on('click', function(e) {
			e.preventDefault();
			var $this = $(this),
				$wrapper = $this.closest('.waic-check-wrapper'),
				$input = $wrapper.find('input, textarea, select');
			$wrapper.find('.waic-setup-check').removeClass('selected');
			if ($input.length == 0) {
				var $selector = $wrapper.attr('data-input');
				if ($selector) $input = _this.content.find($selector);
			}
			if ($input.length == 1) {
				$input.val($this.attr('data-value')).trigger('change');
			}
			$this.addClass('selected');
		});
		_this.content.find('.waic-setup-block-head').on('click', function(e) {
			e.preventDefault();
			var $this = $(this),
				$wrapper = $this.closest('.waic-setup-block'),
				isConnect = $wrapper.hasClass('waic-setup-connect'),
				isOn = $wrapper.hasClass('on'),
				$input = $this.find('.waic-input-toggle input');
			if (isConnect) {
				var engine = _this.engineInput.val(),
					$keyInput = $wrapper.find('.waic-api-key'),
					keys = waicParseJSON(_this.apiKeys.val()) || {};
			}
			if (isOn) {
				if (isConnect) {
					if (!(engine in keys) || keys[engine].length == 0) {
						return false;
					}
				}
				$wrapper.removeClass('on');
				if ($input.length) $input.val(0).trigger('change');
			} else {
				if (isConnect) {
					$keyInput.val(engine in keys && keys[engine].length ? keys[engine] : '');
				}
				$wrapper.addClass('on');
				if ($input.length) $input.val(1).trigger('change');
			}
		});
		_this.content.find('.waic-connect-button').on('click', function(e) {
			e.preventDefault();
			var $this = $(this),
				$wrapper = $this.closest('.waic-setup-block'),
				key = $wrapper.find('.waic-api-key').val();
			if (key.length) {
				var engine = _this.engineInput.val(),
					keys = waicParseJSON(_this.apiKeys.val()) || {};
				keys[engine] = key;
				_this.apiKeys.val(JSON.stringify(keys));
				$wrapper.find('.waic-setup-block-desc').text(_this.maskApiKey(key));
				$wrapper.find('.waic-setup-block-head').trigger('click');
			}
			return false;
		});
		_this.content.find('.waic-ai-engine').on('change', function(e) {
			e.preventDefault();
			var $this = $(this),
				engine = $this.val(),
				isConnect = $this.is('#waicAIEngine'),
				$model = $($this.attr('data-model'));
			if ($model) {
				var modelsList = waicParseJSON($this.attr('data-models')) || {},
					models = engine in modelsList ? modelsList[engine] : {};
				$model.html('');
				for (var k in models) {
					$model.append('<option value="'+k+'">'+models[k]+'</option>'); 
				}
			}
			if (isConnect) {
				var $wrapper = _this.content.find('.waic-setup-connect'),
					keys = waicParseJSON(_this.apiKeys.val()) || {},
					key = (engine in keys) && keys[engine].length ? keys[engine] : '';
				$wrapper.find('.waic-setup-block-desc').text(_this.maskApiKey(key));
				if (key.length) $wrapper.removeClass('on');
				else $wrapper.addClass('on');
			}
			return false;
		});
		_this.content.find('.waic-embed-trigger').on('change', function(e) {
			e.preventDefault();
			var $checked = _this.content.find('.waic-embed-trigger[value="1"]'),
				$options = _this.content.find('.waic-embed-block');
			if ($checked.length) $options.removeClass('wbw-hidden');
			else $options.addClass('wbw-hidden');
		});
		_this.content.find('.waic-test-vector').on('click', function(e){
			e.preventDefault();
			$.sendFormWaic({
				elem: $(this),
				data: {
					mod: 'training',
					action: 'testEmbedConnection',
					params: jsonInputsWaic(_this.createForm, true),
				}
			});
			return false;
		});
		_this.content.find('#waicLaunchChatbot').on('click', function(e) {
			e.preventDefault();
			
			if (!_this.generationFlag) {
				_this.generationFlag = true;
				_this.showWaitingPopup();
				_this.currentStep = 0;
				_this.launchCode = Math.floor(100000 + Math.random() * 900000);

				$.sendFormWaic({
					elem: $(this),
					data: {
						mod: 'chatbots',
						action: 'launchChatbot',
						code: _this.launchCode,
						params: jsonInputsWaic(_this.createForm, true),
					},
					onComplete: function(res) {
						clearInterval(_this.genIntervalId);
						_this.hideWaitingPopup();
						_this.generationFlag = false;
					},
					onSuccess: function(res) {
						if (!res.error && res.data && res.data.taskUrl) {
							_this.setPercentsInPopup(99);
							setTimeout(function() {
								$(location).attr('href', res.data.taskUrl);
							}, 1000);
						}
					}
				});
				_this.genIntervalId = setInterval( function() { 
					$.sendFormWaic({
						elem: $(this),
						data: {
							mod: 'chatbots',
							action: 'getLaunchPercent',
							code: _this.launchCode,
						},
						onSuccess: function(res) {
							if (!res.error && res.data && res.data.percent) {
								_this.setPercentsInPopup(res.data.percent);
							}
						}
					});
				}, 2000);
			}
			return false;
		});

		waicInitColorPicker();
	}
	ChatbotSetupPage.prototype.showWaitingPopup = function () {
		var _this = this.$obj,
			messages = waicParseJSON(_this.waitingPopup.find('#waicWaitingTexts').val()) || {},
			$textWrapper = _this.waitingPopup.find('.waic-waiting-text'),
			index = 0;

		_this.waitingPopup.removeClass('wbw-hidden');
		_this.textIntervalId = setInterval(function() { 
			index = (index + 1) % messages.length; 
			$textWrapper.text(messages[index]); 
		}, 2000);
	}
	ChatbotSetupPage.prototype.setPercentsInPopup = function ( percent ) {
		var _this = this.$obj;
		_this.percent = percent;
		if (_this.percent > 99) _this.percent = 99;
		_this.progressBar.animate({width: _this.percent + '%'}, 1000);
		_this.percentsText.text(_this.percent + '%');
	}
	ChatbotSetupPage.prototype.hideWaitingPopup = function () {
		var _this = this.$obj;
		_this.waitingPopup.addClass('wbw-hidden');
		clearInterval(_this.textIntervalId);
		clearInterval(_this.genIntervalId);
	}
	ChatbotSetupPage.prototype.maskApiKey = function(key) { 
		if (typeof key !== 'string') return ''; 
		var startLen = 7, 
			endLen = 4, 
			maskChar = ' . . . ',
			total = key.length;
		if (total <= startLen + endLen) {
			var visible = key.slice(0, Math.max(0, total - 1)); 
			return visible + (visible.length < total ? maskChar : ''); 
		} 
		var start = key.slice(0, startLen),
			end = key.slice(-endLen); 
		return `${start}${maskChar}${end}`; 
	}
	app.waicChatbotSetupPage = new ChatbotSetupPage();

	$(document).ready(function () {
		app.waicChatbotSetupPage.init();
	});

}(window.jQuery, window));