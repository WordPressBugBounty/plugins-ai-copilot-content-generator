(function ($, app) {
	"use strict";
	function MagictextPage() {
		this.$obj = this;
		return this.$obj;
	}

	MagictextPage.prototype.init = function () {
		var _this = this.$obj;
		_this.isPro = WAIC_DATA.isPro == '1';
		_this.langSettings = waicParseJSON($('#waicLangSettingsJson').val());
		_this.content = $('.wbw-tabs-content');
		_this.fieldsList = _this.content.find('.wbw-sections-list');
		_this.bodySectionsWrapper = _this.content.find('#waicBodySectionsWrapper');
		_this.taskMode = _this.content.find('#waicTaskMode');
		_this.mainButton = _this.content.find('#waicSaveMagictext');
		_this.isReadonly = (_this.mainButton.length == 0);
		_this.taskNameWrapper = $('#waicTaskNameWrapper');
		_this.taskId = _this.content.find('#waicMTId').val();
		_this.addButton = _this.content.find('#waicAddButton');
		_this.enabledCb = _this.content.find('#waic-enabled-cb').find('input[type=checkbox]');
		_this.removeButtons = _this.content.find('.wbw-section-remove');
		_this.backButton = _this.content.find('#waicBackButton');
		_this.restoreButton = _this.content.find('#waicRestore');
		_this.publishButton = _this.content.find('#waicPublishButton');

		_this.eventsMagictextPage();
		if (typeof(_this.initPro) == 'function') _this.initPro();
		if (typeof(_this.initAddons) == 'function') _this.initAddons();


	}

	MagictextPage.prototype.eventsMagictextPage = function () {
		var _this = this.$obj;

		_this.fieldsList.on('click', '.wbw-section-header', function(e){
			e.preventDefault();
			var el = $(this),
				i = el.find('.wbw-section-toggle i'),
				wrapper = el.closest('.wbw-sections-list'),
				options = el.closest('.wbw-section').find('.wbw-section-options');


			if (i.hasClass('fa-chevron-down')){
				wrapper.find('.wbw-section-toggle i.fa-chevron-up').each(function() {
					var $this = $(this);
					$this.removeClass('fa-chevron-up').addClass('fa-chevron-down');
					$this.closest('.wbw-section').find('.wbw-section-options').addClass('wbw-hidden');
				});
				i.removeClass('fa-chevron-down').addClass('fa-chevron-up');
				options.removeClass('wbw-hidden');
			} else {
				i.removeClass('fa-chevron-up').addClass('fa-chevron-down');

				options.addClass('wbw-hidden');
			}
		});
		if (_this.isReadonly) {
			_this.content.find('input, select, button, textarea').prop('disabled', 'disabled');
			return;
		}


		_this.publishButton.click(function(e){
			e.preventDefault();

			var $btn = _this.mainButton,
				$form = _this.content.find('#waicMagictextForm'),
				mod = $btn.attr('data-mod'),
				typ = $btn.attr('data-typ');

			$.sendFormWaic({
				elem: $btn,
				data: {
					mod: mod ? mod : 'magictext',
					action: 'publishTask',
					task_id: _this.taskId,
					typ: typ ? typ : '',
				},
				onSuccess: function(res) {
					if (!res.error && res.data && res.data.taskUrl) {
						jQuery(location).reload();
					}
				}
			});
			location.reload();
		});

		_this.enabledCb.on('change', function(e){
			e.preventDefault();
			var $btn = _this.mainButton,
				$form = _this.content.find('#waicMagictextForm'),
				mod = $btn.attr('data-mod'),
				typ = $btn.attr('data-typ');

			$.sendFormWaic({
				elem: $btn,
				data: {
					mod: mod ? mod : 'magictext',
					action: 'updateStatus',
					task_id: _this.taskId,
					typ: typ ? typ : '',
					params: jsonInputsWaic($form, true),
				},
				onSuccess: function(res) {
				}
			});
			return false;
		});

		_this.backButton.click(function(e){
			e.preventDefault();
			const $bread = $('.wbw-navigation .wbw-tab-nav a');
			if ($bread.length) $bread.get(0).click();
			return false;
		});

		_this.addButton.on('click', function(){
			_this.showEditNameDialog();
		});
		_this.content.find('#waicAddField').on('click', function(e) {
			e.preventDefault();
			var field = $('#waicFieldsForAdd').val(),
				$section = _this.fieldsList.find('.wbw-section[data-field="'+field+'"]');
			if ($section.length) {
				$section.removeClass('wbw-hidden');
				if ($section.find('.wbw-section-options').hasClass('wbw-hidden')) {
					$section.find('.wbw-section-toggle').click();
				}
			}
			$(this).blur();
		});
		_this.fieldsList.find('.wbw-section-remove').on('click', function(e){
			e.preventDefault();
			var $section = $(this).closest('.wbw-section');
			if ($section.length && $section.attr('data-required') != '1') $section.addClass('wbw-hidden');
		});

		_this.content.find('#waicAddBodySection').on('click', function(e) {
			e.preventDefault();
			var $block = $('#waicCustomSectionTpl').clone().removeAttr('id');
			$block.find('.waic-list-number').text(_this.bodySectionsWrapper.find('.wbw-settings-field').length+1);
			$block.find('input').attr('name', 'fields[body][sections][]');
			_this.bodySectionsWrapper.append($block);
		});
		_this.bodySectionsWrapper.on('click', '.wbw-elem-remove', function(e){
			e.preventDefault();
			$(this).closest('.wbw-settings-field').remove();
			_this.bodySectionsRenum();
		});
		_this.bodySectionsWrapper.sortable({
			cursor: "move",
			axis: "y",
			placeholder: "sortable-placeholder",
			stop: function (e, ui) {
				_this.bodySectionsRenum();
			},

		});

		_this.removeButtons.on('click', function(e){
			e.preventDefault();

			var $section = $(this).attr('data-field');
			var $list = _this.fieldsList.find('.wbw-section');
			for(var i=0;i<$list.length;i++){

				var $item = $($list[i]);

				if($item.attr('data-field')==$section){
					$item.addClass('wbw-hidden');

					$item.remove();
					break;
				}
			}

		});



		_this.content.find('.wbw-sections-list').sortable({
			update: function(event, ui){
				var $order = $(this).sortable('toArray', {attribute: 'data-id'});

				var data = {
					order: JSON.stringify($order),
				};

				var $btn = _this.mainButton,
					$form = _this.content.find('#waicMagictextForm'),
					mod = $btn.attr('data-mod'),
					typ = $btn.attr('data-typ');

				$.sendFormWaic({
					elem: $btn,
					data: {
						mod: mod ? mod : 'magictext',
						action: 'startGeneration',
						task_id: _this.taskId,
						typ: typ ? typ : '',
						params: jsonInputsWaic($form, true),
					},
					onSuccess: function(res) {
					}
				});
			}
		});
		_this.content.find('.wbw-sections-list').disableSelection();
		var savedOrder = $('#order').val();
		if(savedOrder){
			savedOrder = JSON.parse(savedOrder);
			savedOrder.forEach(function(id){
				$("#"+id).appendTo('.wbw-sections-list');
			});
		}

		_this.mainButton.on('click', function(e){
			e.preventDefault();
			if (typeof(_this.beforeSaveAddon) == 'function') _this.beforeSaveAddon();
			if (typeof(_this.beforeSavePro) == 'function') _this.beforeSavePro();

			var $btn = $(this),
				$form = _this.content.find('#waicMagictextForm'),
				mod = $btn.attr('data-mod'),
				typ = $btn.attr('data-typ');

			_this.fieldsList.find('.wbw-section').each(function(){
				var $section = $(this);
				if ($section.hasClass('wbw-hidden')) $section.find(':input').addClass('wbw-nosave');
				else $section.find(':input').removeClass('wbw-nosave');
			});

			$.sendFormWaic({
				elem: $btn,
				data: {
					mod: mod ? mod : 'magictext',
					action: 'startGeneration',
					task_id: _this.taskId,
					typ: typ ? typ : '',
					params: jsonInputsWaic($form, true),
				},
				onSuccess: function(res) {
					if (!res.error && res.data && res.data.taskUrl) {
						jQuery(location).attr('href', res.data.taskUrl);
					}
				}
			});
			return false;
		});

		_this.content.find('.wbw-button-restore').click(function(e){
			e.preventDefault();
			waicShowConfirm(waicCheckSettings(_this.langSettings, 'confirm-restore'), 'waicMagictextPage', 'restoreOptions', $(this));
			return false;
		});


		_this.content.find('.wbw-button-back').click(function(e){
			e.preventDefault();
			var $bread = $('.wbw-bread-crumbs a.wbw-bread-link');
			if ($bread.length) $bread.get(0).click();
			return false;
		});
	}
	MagictextPage.prototype.restoreOptions = function ($btn) {
		var $from = $btn.closest('form');
		$.sendFormWaic({
			elem: $btn,
			data: {
				mod: 'magictext',
				action: 'restoreOptions',
				group: 'all',
				task_id: $('#waicMTId').val()
			},
			onSuccess: function(res) {
				if (!res.error) {
					location.reload();
				}
			}
		});
	}
	MagictextPage.prototype.showEditNameDialog = function () {
		var _this = this.$obj;
		_this.taskTitleInput = _this.content.find('#waicTaskTitle');

		if (!_this.postEditNameDialog) {
			_this.postEditNameDialog = $('#waicEditNameDialog').removeClass('wbw-hidden').dialog({
				modal: true,
				autoOpen: false,
				position: {my: 'center', at: 'center', of: window},
				width: '500px',
				dialogClass: "wbw-plugin",
				buttons: [
					{
						text: waicCheckSettings(_this.langSettings, 'add-btn'),
						class: 'wbw-button wbw-button-form wbw-button-main',
						click: function(e) {

							e.preventDefault();

							let len = _this.fieldsList.find('.wbw-section').length;
							let val = _this.postEditNameDialog.find('#waicNewTaskName').val();

							let section = jQuery('#waic-mt-new-item-template').html();
							section = section.replaceAll("{title}", val);
							section = section.replaceAll("{data_field}", 'c_prompt_' + (len+1));
							section = section.replaceAll("{wbw_tooltip}", 'wbw-tooltip');

							_this.fieldsList.append(section);
							waicInitTooltips('.wbw-options-block');
							jQuery('.wbw-section-remove').on('click', function (e) {
								e.preventDefault();

								var $section = $(this).attr('data-field');
								var $list = _this.fieldsList.find('.wbw-section');
								for(var i=0;i<$list.length;i++){

									var $item = $($list[i]);

									if($item.attr('data-field')==$section){
										$item.addClass('wbw-hidden');

										$item.remove();
										break;
									}
								}
							})

							jQuery(this).dialog('close');
						}
					},
					{
						text: waicCheckSettings(_this.langSettings, 'cancel-btn'),
						class: 'wbw-button wbw-button-form wbw-button-minor',
						click: function() {
							jQuery(this).dialog('close');
						}
					}
				],
				open: function() {
					_this.postEditNameDialog.find('#waicNewTaskName').val(_this.taskTitleInput.val());
				},
			});
		}
		_this.postEditNameDialog.dialog('open');
	}
	MagictextPage.prototype.bodySectionsRenum = function () {
		var _this = this.$obj;
		_this.bodySectionsWrapper.find('.waic-list-number').each(function(index) {
			$(this).text(index+1);
		});

	}

	app.waicMagictextPage = new MagictextPage();

	$(document).ready(function () {
		app.waicMagictextPage.init();
	});

}(window.jQuery, window));