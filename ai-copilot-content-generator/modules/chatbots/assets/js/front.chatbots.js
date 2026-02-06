(function ($, app) {
"use strict";
	function ChatbotFrontPage() {
		this.$obj = this;
		this.$obj.emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
		return this.$obj;
	}
	
	ChatbotFrontPage.prototype.init = function ($wrapper) {
		var _this = this.$obj,
			$wrapper = typeof($wrapper) == 'undefined' ? $('.waic-chatbot-widget-wrapper') : $wrapper;
		if ($wrapper.length == 0) return;
		
		_this.chatbots =  $wrapper.find('.waic-chatbot-widget');
		_this.addCustomCss();
		_this.moveFloatingElements();
		
		
		_this.eventsChatbotFrontPage();
		if (typeof(_this.initPro) == 'function') _this.initPro();
		if (typeof(_this.initAddons) == 'function') _this.initAddons();
		
		if (app.waicChatbotAdminPage) _this.scrollBody(_this.chatbots);
		else {
			_this.initShowWelcome();
			_this.initAutostart();
		}
	}
	ChatbotFrontPage.prototype.addCustomCss = function () {
		var _this = this.$obj;
		if ($('style#waicCustomCss').length === 0) {
			var cssCodeStr = '';
			_this.chatbots.each(function () {
				var $widget = $(this);
				if ($widget.attr('data-preview') != 1) {
					var customCss = $widget.find('style');
					if (customCss.length) {
						cssCodeStr += customCss.html();
						customCss.remove();
					}
				}
			});
			if (cssCodeStr.length > 0) {
				$('<style type="text/css" id="waicCustomCss">' + cssCodeStr + '</style>').appendTo('head');
			}
		}
	}
	ChatbotFrontPage.prototype.initAutostart = function () {
		var _this = this.$obj;
		$('.waic-chatbot-open img').each(function() {
			var $btnImg = $(this),
				autostart = parseInt($btnImg.attr('data-autostart')) * 1000;
			if (autostart > 0) {
				setTimeout(function() {
					if ($btnImg.is(':visible')) $btnImg.trigger('click');
				}, autostart);
			}
		});
	}
	ChatbotFrontPage.prototype.initShowWelcome = function () {
		var _this = this.$obj;
		$('.waic-chatbot-welcome').each(function() {
			var $block = $(this),
				pause = $block.attr('data-autoshow');
			if (typeof(pause) != 'undefined') {
				pause = pause * 1000;
				setTimeout(function() {
					_this.showWelcomePopup(0, false, $block);
				}, pause);
			}
		});
		$('.waic-chatbot-welcome .waic-close-popup').off('click').on('click', function(e) {
			e.preventDefault();
			$(this).closest('.waic-chatbot-welcome').addClass('waic-welcome-hidden');
		});
	}
	ChatbotFrontPage.prototype.showWelcomePopup = (function (id, message, $block) {
		if (typeof($block) == 'undefined') {
			var _this = this.$obj,
				$chatbot = _this.getChatbot(id);
			if ($chatbot && $chatbot.length) $block = $chatbot.find('.waic-chatbot-welcome');
		}
		if ($block && $block.length) {
			var $widget = $block.closest('.waic-chatbot-wrapper');
			if ($block.hasClass('waic-welcome-hidden') && $widget.length && $widget.find('.waic-chatbot-panel').hasClass('waic-chatbot-hidden')) {
				if (typeof(message) != 'undefined' && message !== false) {
					$block.find('.waic-welcome-text').html(message);
				}
				$block.removeClass('waic-welcome-hidden');
			}
		}
	});
	ChatbotFrontPage.prototype.moveFloatingElements = (function () {
		var _this = this.$obj;
		_this.chatbots.each(function () {
			var $widget = $(this);
			if ($widget.attr('data-preview') != 1) {
				var $fixed = $widget.find('.waic-need-fixed').removeClass('waic-need-fixed');
				if ($fixed.length == 1) {
					var $fCopy = $fixed.clone();
					$widget.closest('.waic-chatbot-widget-wrapper').attr('id', $widget.attr('id')).find('.waic-chatbot-fixed').append($fCopy);
				}
				$('body').append($widget);
			}
		});
		$('.waic-chatbot-widget-wrapper').show();
	});

	ChatbotFrontPage.prototype.eventsChatbotFrontPage = function () {
		var _this = this.$obj;
		
		_this.chatbots.find('.waic-header-close').off('click').on('click', function(e) {
			e.preventDefault();
			$(this).closest('.waic-chatbot-widget').find('.waic-chatbot-close').trigger('click');
		});
		_this.chatbots.find('.waic-chatbot-close').off('click').on('click', function(e) {
			e.preventDefault();
			var viewId = $(this).closest('.waic-chatbot-buttons').attr('data-viewid'),
				$buttons = $('.waic-chatbot-buttons.waic-' + viewId),
				$panel = $('.waic-chatbot-panel.waic-' + viewId),
				$wrapperFull = $panel.closest('.waic-chatbot-float.waic-full-mobile'),
				$duration = parseFloat($panel.css('transition-duration')) * 1000;
			$panel.removeClass('waic-chatbot-show');
			setTimeout(function () {
				if (!$panel.hasClass('waic-chatbot-show')) {
					$panel.addClass('waic-chatbot-hidden');
				}
				$wrapperFull.removeClass('waic-full-show');
			}, $duration);
			$buttons.find('.waic-chatbot-close').addClass('waic-chatbot-hidden');
			$buttons.find('.waic-chatbot-open').removeClass('waic-chatbot-hidden');
		});
		_this.chatbots.find('.waic-chatbot-open').off('click').on('click', function(e) {
			e.preventDefault();
			var viewId = $(this).closest('.waic-chatbot-buttons').attr('data-viewid'),
				$buttons = $('.waic-chatbot-buttons.waic-' + viewId),
				$panel = $('.waic-chatbot-panel.waic-' + viewId),
				$wrapperFull = $panel.closest('.waic-chatbot-float.waic-full-mobile');
			$panel.closest('.waic-chatbot-wrapper').find('.waic-chatbot-welcome').addClass('waic-welcome-hidden');

			$panel.removeClass('waic-chatbot-hidden');
			setTimeout(function () {
				$panel.addClass('waic-chatbot-show');
				$wrapperFull.addClass('waic-full-show');
			}, 2);
			$buttons.find('.waic-chatbot-close').removeClass('waic-chatbot-hidden');
			$buttons.find('.waic-chatbot-open').addClass('waic-chatbot-hidden');
			_this.scrollBody($panel.closest('.waic-chatbot-widget'));
		});
		$('.waic-chatbot-fixed .waic-chatbot-open').off('click').on('click', function(e) {
			e.preventDefault();
			var $fixed = $(this).closest('.waic-chatbot-fixed');
			if ($fixed.length) {
				$('.waic-chatbot-widget.waic-' + $fixed.attr('data-viewid')).find('.waic-chatbot-open').trigger('click');
			}
		});
		$('.waic-chatbot-fixed .waic-chatbot-close').off('click').on('click', function(e) {
			e.preventDefault();
			var $fixed = $(this).closest('.waic-chatbot-fixed');
			if ($fixed.length) {
				$('.waic-chatbot-widget.waic-' + $fixed.attr('data-viewid')).find('.waic-chatbot-close').trigger('click');
			}
		});
		_this.chatbots.find('.waic-chatbot-body').off('click').on('click', '.waic-human-request', function(e) {
			e.preventDefault();
			_this.sendRequest($(this));
			return false;
		});
		_this.chatbots.find('.waic-message-clip').off('click').on('click', function(e) {
			e.preventDefault();
			if (!$(this).hasClass('waic-disabled')) $(this).parent().find('.waic-chatbot-upload input').trigger('click');
			return false;
		});
		_this.chatbots.find('.waic-chatbot-upload input').off('change').on('change', function(e) {
			e.preventDefault();
			var $input = $(this),
				$form = $input.closest('form.waic-chatbot-upload'),
				$widget = $form.closest('.waic-chatbot-widget'),
				$wrap = $widget.find('.waic-message-clip'),
				maxSize = parseInt($wrap.attr('data-max-size')),
				error = false;
			if (!this.files || this.files.length < 1) {
				error = true;
			} else {
				if (this.files.length > 1) {
					_this.addAlert($widget, $wrap.attr('data-too-many'));
					error = true;
				} else if (maxSize && this.files[0].size > maxSize) {
					_this.addAlert($widget, $wrap.attr('data-too-big'));
					error = true;
				}
			}
			if (error) $input.val('');
			else $form.submit();
			return false;
		});
		_this.chatbots.find('.waic-chatbot-upload').off('submit').on('submit', function(e) {
			e.preventDefault();
			var $form = $(this),
				form = new FormData,
				$widget = $form.closest('.waic-chatbot-widget'),
				taskId = parseInt($widget.attr('data-task-id')),
				mode = $widget.attr('data-preview'),
				chatId = $widget.attr('id'),
				$input = $widget.find('.waic-chatbot-input'),
				message = $input.val();
			if (taskId == NaN || taskId == 0) {
				if (mode == 1 && app.waicChatbotAdminPage) waicShowAlert(waicCheckSettings(app.waicChatbotAdminPage.langSettings, 'need-save'));
				return false;
			}
			form.append('img', $form.find('input').prop('files')[0]);
			var mesId = _this.addMessage($widget, message, 'file');
			$input.val('');
			_this.disableChatbot($widget, true);
			
			$.sendFormWaic({
				form: form,
				appendData: {
					mod: 'chatbots',
					action: 'sendFile',
					task_id: taskId,
					chat_id: chatId,
					mes_id: mesId,
					mode: mode,
					message: message,
					aware: message.length ? _this.getAware($widget) : ''
				},
				ajax: {
					processData: false,
					contentType: false,
				},
				onSuccess: function(res) {
					if (!res.error && res.data) {
						if (!res.data['error'] && res.data['log']) {
							_this.replaceMessage(res.data['log']);
						}
					}
				}
			});

			return false;
		});
		_this.chatbots.find('.waic-message-send').off('click').on('click', function(e) {
			e.preventDefault();
			if (!$(this).hasClass('waic-disabled')) _this.sendRequest($(this));
		});
		_this.chatbots.find('.waic-chatbot-input').off('keydown').on('keydown', function(e) {
			if (e.key === 'Enter' || e.keyCode === 13) {
				if (!e.shiftKey) {
					e.preventDefault();
					$(this).closest('.waic-chatbot-widget').find('.waic-message-send').trigger('click');
				}
			}
		});
		
		_this.chatbots.find('.waic-chatbot-messages').on('click', '.waic-chatbot-card', function(e) {
			if (!$(e.target).is('a, button')) {
				var href = $(this).attr('data-href');
				if (href) window.open(href, '_blank');
			}
		});
	}
	ChatbotFrontPage.prototype.scrollBody = function ($widget, dur) {
		var $chatBody = $widget.find('.waic-chatbot-body'),
			dur = typeof(dur) == 'undefined' ? 0 : dur;
		$chatBody.animate({scrollTop: $chatBody.prop('scrollHeight')}, dur);
	}
		
	ChatbotFrontPage.prototype.sendRequest = function ($elem) {
		var _this = this.$obj,
			$widget = $elem.closest('.waic-chatbot-widget'),
			taskId = parseInt($widget.attr('data-task-id')),
			mode = $widget.attr('data-preview'),
			chatId = $widget.attr('id'),
			$input = $widget.find('.waic-chatbot-input'),
			isHumanReq = $elem.hasClass('waic-human-request'),
			message = isHumanReq ? '' : $input.val(),
			typ = isHumanReq ? 'text' : $input.attr('type'),
			request = isHumanReq ? 'human' : $input.attr('data-request');
		if (taskId == NaN || taskId == 0) {
			if (mode == 1 && app.waicChatbotAdminPage) waicShowAlert(waicCheckSettings(app.waicChatbotAdminPage.langSettings, 'need-save'));
			return false;
		}
		if (message.length || isHumanReq) {
			var mesId = _this.addMessage($widget, message.replace(/\n/g,'<br>'), request);
			$input.val('');
			
			$.sendFormWaic({
				data: {
					mod: 'chatbots',
					action: 'sendMessage',
					task_id: taskId,
					chat_id: chatId,
					mes_id: mesId,
					mode: mode,
					typ: typ,
					request: request,
					message: message,
					aware: isHumanReq || typ != 'text' ? '' : _this.getAware($widget)
				},
				onSuccess: function(res) {
					if (!res.error && res.data) {
						if (res.data['error']) {
							//_this.addMessage(res.data);
						} else {
							if (res.data['log']) {
								var result = modifyDataThroughEvent('aiwuModifyReply', res.data['log']);
								_this.replaceMessage(result);
							}
						}
						$input.focus();
					}
				}
			});
		}
	}
	ChatbotFrontPage.prototype.getAware = function ($widget) {
		var _this = this.$obj,
			selectors = $widget.attr('data-aware'),
			aware = '';
		if (selectors.length) {
			selectors = waicParseJSON(selectors);
			console.log(selectors);
			selectors.forEach((selector) => {
				var $elem = $(selector);
				if ($elem.length) aware += $elem.clone().find('script,style,.waic-chatbot-widget,.waic-chatbot-widget-wrapper').remove().end().text();
			});
		}
		return aware;
	}
	ChatbotFrontPage.prototype.addMessage = function ($widget, message, request) {
		var _this = this.$obj,
			isHumanReq = 'human' == request,
			isFileUpload = 'file' == request,
			$block = isHumanReq && message.length == 0 ? false : $widget.find('.waic-chatbot-tmp.waic-message-user').clone().removeClass('waic-chatbot-tmp'),
			mesId = $widget.attr('data-last-mes'),
			$messages = $widget.find('.waic-chatbot-messages');
		if (typeof(mesId) == 'undefined') mesId = 0;
		else mesId = parseInt(mesId);
		mesId++;
		if ($block) {
			if (isFileUpload && message.length) {
				$block.find('.waic-message-text').html(message);
				$messages.append($block);
				$block = $widget.find('.waic-chatbot-tmp.waic-message-user').clone().removeClass('waic-chatbot-tmp');
			}
			$block.attr('data-ques-id', mesId);
			if (!isFileUpload) $block.find('.waic-message-text').html(message);
			$messages.append($block);
		}
		$block = isFileUpload ? false : $widget.find('.waic-chatbot-tmp.waic-message-ai').clone().removeClass('waic-chatbot-tmp').attr('data-mes-id', mesId);
		if ($block) {
			$messages.append($block);
		}
		$widget.attr('data-last-mes', mesId);
		_this.disableChatbot($widget, true);
		_this.scrollBody($widget, 1000);

		return mesId;
	}
	ChatbotFrontPage.prototype.replaceMessage = function ($data, $mesId) {
		var _this = this.$obj;
		if ($data['chat_id'] && $data['mes_id']) {
			var $widget = $('.waic-chatbot-widget.'+$data['chat_id']);
			if ($widget.length) {
				var $aiMes = $widget.find('.waic-chatbot-message[data-mes-id="'+$data['mes_id']+'"]'),
					$userMes = $widget.find('.waic-chatbot-message[data-ques-id="'+$data['mes_id']+'"]'),
					$input = $widget.find('.waic-chatbot-input'),
					addAi = false;
				if ($userMes.length) {
					if ($data['question']) $userMes.find('.waic-message-text').html($data['question']);
					if ($data['tt']) $userMes.find('.waic-message-time').html($data['tt']);
				}
				if ($aiMes.length == 0 && $data['answer'] && $data['answer'].length) {
					addAi = true;
					$aiMes = $widget.find('.waic-chatbot-tmp.waic-message-ai').clone().removeClass('waic-chatbot-tmp');
				}
				if ($aiMes.length) {
					if ($data['answer']) $aiMes.find('.waic-message-text').html($data['answer']);
					if ($data['cards'] && $data['cards'].length) {
						var $aiMesText = $aiMes.find('.waic-message-text');
						$data['cards'].forEach(function(html, index) {
							$aiMesText.after($(html));
						});
					}
					if ($data['tt']) $aiMes.find('.waic-message-time').html($data['tt']);
					if ($data['btn']) {
						var $btns = $aiMes.find('.waic-message-buttons');
						if ($btns.length) {
							$data['btn'].forEach((btn) => {
								if (!btn['uniq'] || $widget.find(btn['uniq']).length == 0) {
									$btns.append($('<a href="'+btn['link']+'" class="'+btn['class']+'">'+btn['name']+'</a>'));
								}
							});
						}
					}
					if (addAi) $widget.find('.waic-chatbot-messages').append($aiMes);
				}
				
				if ($data['need_email'] == 1) {
					$input.attr('type', 'email');
					if ($data['request']) $input.attr('data-request', $data['request']);
					if ($data['plh_email']) $input.attr('placeholder', $data['plh_email']);
				} else {
					$input.attr('type', 'text').attr('placeholder', $input.attr('data-plh')).attr('data-request', '');
				}
				_this.disableChatbot($widget, $data['disable'] == 1);
			}
			$widget.find('.waic-chatbot-message:not(.waic-chatbot-tmp) .waic-message-loader').closest('.waic-chatbot-message').addClass('waic-chatbot-hidden');
			_this.scrollBody($widget, 1000);
		}
	}
	ChatbotFrontPage.prototype.addAlert = function ($widget, message) {
		var _this = this.$obj,
			$block = $widget.find('.waic-chatbot-tmp.waic-message-ai').clone().removeClass('waic-chatbot-tmp');
		if ($block) {
			$block.find('.waic-message-text').html(message);
			$widget.find('.waic-chatbot-messages').append($block);
			_this.scrollBody($widget, 1000);
		}
	}
	ChatbotFrontPage.prototype.disableChatbot = function ($widget, $disable) {
		var $input = $widget.find('.waic-chatbot-input');
		if ($disable) {
			$input.attr('placeholder', '').attr('disabled', 'disabled');
			$widget.find('.waic-message-action').addClass('waic-disabled');
		} else {
			$input.removeAttr('disabled');
			$widget.find('.waic-message-action').removeClass('waic-disabled');
		}
	}
	// Client-Side API 
	ChatbotFrontPage.prototype.getChatbot = function (id) {
		var _this = this.$obj;
		if (_this.chatbots.length == 0) return false;
		if (typeof id == 'undefined') return _this.chatbots.first();
		return _this.chatbots.filter('[data-task-id='+id+']');
		//var $input = $widget.find('.waic-chatbot-input');
	}
	ChatbotFrontPage.prototype.openChatbot = function (id) {
		var _this = this.$obj,
			$chatbot = _this.getChatbot(id);
		if ($chatbot && $chatbot.length) {
			var viewId = $chatbot.attr('data-viewid');
			$('.waic-chatbot-buttons.waic-'+viewId+' .waic-chatbot-open').click();
		}
	}
	ChatbotFrontPage.prototype.closeChatbot = function (id) {
		var _this = this.$obj,
			$chatbot = _this.getChatbot(id);
		if ($chatbot && $chatbot.length) {
			var viewId = $chatbot.attr('data-viewid');
			$('.waic-chatbot-buttons.waic-'+viewId+' .waic-chatbot-close').click();
		}
	}
	ChatbotFrontPage.prototype.askChatbot = function (question, id) {
		if (typeof question == 'undefined') return;
		var _this = this.$obj,
			$chatbot = _this.getChatbot(id);
		if ($chatbot && $chatbot.length) {
			var viewId = $chatbot.attr('data-viewid'),
				$panel = $('.waic-chatbot-panel.waic-'+viewId);
			if ($panel.length) {
				$panel.find('.waic-chatbot-input').val(question);
				$panel.find('.waic-message-send').click();
			}
		}
	}
	function modifyDataThroughEvent(filter, data) {
		let modifiedData = null;
		const event = new CustomEvent(filter, {
			detail: {
				original: data,
				setResult: (value) => { modifiedData = value; }
			}
		});
		document.dispatchEvent(event);
		return modifiedData ?? data;
	}
	
	app.aiwuChatbot = new ChatbotFrontPage();

	$(document).ready(function () {
		app.aiwuChatbot.init();
	});

}(window.jQuery, window));
