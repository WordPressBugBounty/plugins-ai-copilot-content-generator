(function ($, app) {
"use strict";
	function SettingsPage() {
		this.$obj = this;
		return this.$obj;
	}
	
	SettingsPage.prototype.init = function () {
		var _this = this.$obj;
		_this.isPro = WAIC_DATA.isPro == '1';
		_this.langSettings = waicParseJSON($('#waicLangSettingsJson').val());
		_this.content = $('.wbw-tabs-content');
		
		_this.eventsSettingsPage();
		if (typeof(_this.initPro) == 'function') _this.initPro();
	}
	
	SettingsPage.prototype.eventsSettingsPage = function () {
		var _this = this.$obj;
		_this.content.find('.wbw-button-save').click(function(e){
			e.preventDefault();
			var $btn = $(this),
				$from = $btn.closest('form');
			$.sendFormWaic({
				elem: $btn,
				data: {
					mod: 'options',
					action: 'saveOptions',
					group: $from.attr('data-group'),
					params: jsonInputsWaic($from, true),
				},
			});
			return false;
		});
		_this.content.find('#waicStartGeneration').click(function(e){
			e.preventDefault();
			var $btn = $(this),
				$from = $btn.closest('form');
			$.sendFormWaic({
				elem: $btn,
				data: {
					mod: 'workspace',
					action: 'runGeneration'
				},
			});
			return false;
		});
		_this.content.find('.wbw-button-cancel').click(function(e){
			e.preventDefault();
			location.reload();
			return false;
		});
		_this.content.find('.wbw-button-restore').click(function(e){
			e.preventDefault();
			waicShowConfirm(waicCheckSettings(_this.langSettings, 'confirm-restore'), 'waicSettingsPage', 'restoreOptions', $(this));
			return false;
		});
		_this.content.find('#waicGenarateMCPToken').click(function(e){
			e.preventDefault();
			const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
			var token = '';
			for (var i = 0; i < 32; i++) {
				token += chars.charAt(Math.floor(Math.random() * chars.length));
			}
			_this.content.find('#waicMCPToken').val(token);
			// Sync all MCP URL fields that have a base URL stored in data-mcp-base-url
			_this.content.find('.waic-mcp-url-field').each(function(){
				var $f = $(this),
					base = $f.attr('data-mcp-base-url');
				if (base) {
					$f.val(base + '?token=' + token);
					// Re-mask after regeneration so the new token isn't exposed
					if (!$f.hasClass('waic-fake-password')) {
						$f.addClass('waic-fake-password');
					}
				}
			});
			// Reset View buttons back to "View" label
			_this.content.find('.waic-view-btn').text(waicCheckSettings(_this.langSettings, 'btn-view'));
			return false;
		});
		_this.content.find('#waicViewMCPToken').click(function(e){
			e.preventDefault();
			var $this = $(this),
				$token = _this.content.find('#waicMCPToken');
			if ($token.hasClass('waic-fake-password')) {
				$token.removeClass('waic-fake-password');
				$this.text(waicCheckSettings(_this.langSettings, 'btn-hide'));
			} else {
				$token.addClass('waic-fake-password');
				$this.text(waicCheckSettings(_this.langSettings, 'btn-view'));
			}
			return false;
		});
		// Generic View toggle for masked URL/secret fields (class-based, reusable)
		_this.content.on('click', '.waic-view-btn', function(e){
			e.preventDefault();
			var $btn = $(this),
				target = $btn.attr('data-target'),
				$field = target ? _this.content.find(target) : null;
			if (!$field || !$field.length) { return false; }
			if ($field.hasClass('waic-fake-password')) {
				$field.removeClass('waic-fake-password');
				$btn.text(waicCheckSettings(_this.langSettings, 'btn-hide'));
			} else {
				$field.addClass('waic-fake-password');
				$btn.text(waicCheckSettings(_this.langSettings, 'btn-view'));
			}
			return false;
		});
		// Generic Copy-to-clipboard handler with success/failure feedback
		_this.content.on('click', '.waic-copy-btn', function(e){
			e.preventDefault();
			var $btn = $(this),
				target = $btn.attr('data-target'),
				$field = target ? _this.content.find(target) : null;
			if (!$field || !$field.length) { return false; }
			var value = $field.val(),
				originalText = $btn.data('original-text') || $btn.text();
			$btn.data('original-text', originalText);

			var onOk = function(){
				$btn.text(waicCheckSettings(_this.langSettings, 'btn-copied'));
				setTimeout(function(){ $btn.text(originalText); }, 1500);
			};
			var onFail = function(){
				$btn.text(waicCheckSettings(_this.langSettings, 'btn-copy-fail'));
				setTimeout(function(){ $btn.text(originalText); }, 1500);
			};

			if (navigator.clipboard && window.isSecureContext) {
				navigator.clipboard.writeText(value).then(onOk, function(){
					// Fallback on clipboard API failure
					try {
						var wasReadonly = $field.prop('readonly');
						$field.prop('readonly', false).select();
						var ok = document.execCommand('copy');
						$field.prop('readonly', wasReadonly).blur();
						ok ? onOk() : onFail();
					} catch (err) { onFail(); }
				});
			} else {
				try {
					var wasReadonly = $field.prop('readonly');
					$field.prop('readonly', false).select();
					var ok = document.execCommand('copy');
					$field.prop('readonly', wasReadonly).blur();
					ok ? onOk() : onFail();
				} catch (err) { onFail(); }
			}
			return false;
		});

		var $instraction = _this.content.find('#waicMCPInstructions'),
			$tabsButtons = $instraction.find('.wbw-submenu-tabs button.wbw-button'),
			$tabsContents = jQuery('.wbw-subtabs-content .wbw-subtab-content'),
			$curTab = $tabsButtons.filter('.current');
		$tabsContents.filter($curTab.attr('data-content')).addClass('active');

		$tabsButtons.on('click', function (e) {
			e.preventDefault();
			var $this = jQuery(this),
				$curTab = $this.attr('data-content');

			$tabsContents.removeClass('active');
			$tabsButtons.removeClass('current');
			$this.addClass('current');
			$this.blur();

			$tabsContents.filter($curTab).addClass('active');//.trigger('waic-tab-change');
		});
	}
	SettingsPage.prototype.restoreOptions = function ($btn) {
		var $from = $btn.closest('form');
		$.sendFormWaic({
			elem: $btn,
			data: {
				mod: 'options',
				action: 'restoreOptions',
				group: $from.attr('data-group')
			},
			onSuccess: function(res) {
				if (!res.error) {
					location.reload();
				}
			}
		});
	}
	
	app.waicSettingsPage = new SettingsPage();

	$(document).ready(function () {
		app.waicSettingsPage.init();
	});

}(window.jQuery, window));
