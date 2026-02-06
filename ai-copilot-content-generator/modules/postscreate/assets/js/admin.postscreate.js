(function ($, app) {
"use strict";
	function PostsCreatePage() {
		this.$obj = this;
		return this.$obj;
	}
	
	PostsCreatePage.prototype.init = function () {
		var _this = this.$obj;
		_this.isPro = WAIC_DATA.isPro == '1';
		//_this.dateFormat = WAIC_DATA.dateFormat;
		_this.langSettings = waicParseJSON($('#waicLangSettingsJson').val());
		_this.content = $('.wbw-tabs-content');
		_this.fieldsList = _this.content.find('.wbw-sections-list');
		_this.bodySectionsWrapper = _this.content.find('#waicBodySectionsWrapper');
		_this.taskMode = _this.content.find('#waicTaskMode');
		_this.mainButton = _this.content.find('#waicStartGeneration');
		_this.isReadonly = (_this.mainButton.length == 0);
		_this.taskNameWrapper = $('#waicTaskNameWrapper');
		_this.taskId = _this.content.find('#waicPCId').val();
		
		_this.eventsPostsCreatePage();
		if (typeof(_this.initPro) == 'function') _this.initPro();
		if (typeof(_this.initAddons) == 'function') _this.initAddons();
	}
	
	PostsCreatePage.prototype.eventsPostsCreatePage = function () {
		var _this = this.$obj;
		
		_this.fieldsList.find('.wbw-section-header').on('click', function(e){
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
		if (_this.taskNameWrapper.length) {
			_this.nameEditBtn = $('<i class="fa fa-fw fa-pencil waic-name-edit-btn">');
			_this.taskNameWrapper.parent().append(_this.nameEditBtn);
			_this.nameEditBtn.on('click', function() {
				_this.showEditNameDialog();
			});
		}
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
			/*start: function (e, ui) {
				startFilterPosition = ui.item.index();
			},*/
		});
		//_this.mainButton = _this.content.find('#waicStartGeneration');
		/*_this.content.find('#waicBodyMode').on('change', function() {
			var mode = $(this).val();
			_this.mainButton.text(_this.mainButton.attr('data-' + (mode == 'sections' ? 'sections' : 'default')));
		});*/
		_this.mainButton.on('click', function(e){
			e.preventDefault();
			if (typeof(_this.beforeSaveAddon) == 'function') _this.beforeSaveAddon();
			if (typeof(_this.beforeSavePro) == 'function') _this.beforeSavePro();
			
			var $btn = $(this),
				$form = _this.content.find('#waicPostsCreateForm'),
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
					mod: mod ? mod : 'postscreate',
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
		_this.content.find('.wbw-button-back').click(function(e){
			e.preventDefault();
			var $bread = $('.wbw-navigation .wbw-tab-nav a');
			if ($bread.length) $bread.get(0).click();
			return false;
		});
	}
	PostsCreatePage.prototype.showEditNameDialog = function () {
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
							var newName = _this.postEditNameDialog.find('#waicNewTaskName').val();
							if (_this.taskId > 0) {
								$.sendFormWaic({
									icon: _this.nameEditBtn,
									data: {
										mod: 'workspace',
										action: 'editTaskName',
										task_id: _this.taskId,
										new_name: newName
									},
									onSuccess: function(res) {
										if (!res.error && res.data && res.data.title) {
											_this.taskTitleInput.val(res.data.title);
											_this.taskNameWrapper.text(res.data.title);
										}
									},
								});
							} else {
								_this.taskTitleInput.val(newName);
								_this.taskNameWrapper.text(newName);
							}
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
	PostsCreatePage.prototype.bodySectionsRenum = function () {
		var _this = this.$obj;
		_this.bodySectionsWrapper.find('.waic-list-number').each(function(index) {
			$(this).text(index+1);
		});
		
	}
	
	app.waicPostsCreatePage = new PostsCreatePage();

	$(document).ready(function () {
		app.waicPostsCreatePage.init();
	});

}(window.jQuery, window));