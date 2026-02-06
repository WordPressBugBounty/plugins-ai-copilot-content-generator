(function ($, app) {
"use strict";
	function ChatbotAdminPage() {
		this.$obj = this;
		return this.$obj;
	}
	
	ChatbotAdminPage.prototype.init = function () {
		var _this = this.$obj;
		_this.waicWaitPreviewLoad = true;
		_this.waicWaitResponse = false;
		_this.waicNeedPreview = false;
		_this.isPro = WAIC_DATA.isPro == '1';

		_this.langSettings = waicParseJSON($('#waicLangSettingsJson').val());
		_this.content = $('.wbw-tabs-content');
		_this.createForm = _this.content.find('#waicChatbotCreateForm');

		_this.preview = $('.wbw-preview-content');
		_this.previewBlock = $('#waicChatbotPreviewBlock');
		_this.mainButtons = _this.content.find('#waicMainButtons');
		_this.mainButton = _this.content.find('#waicSaveTask');
		_this.taskNameWrapper = $('#waicTaskNameWrapper');
		_this.taskId = _this.content.find('#waicPCId').val();
		
		_this.historyTable = _this.content.find('#waicHistoryTable');
		//_this.historyTableLoaded = false;
		
		_this.eventsChatbotAdminPage();
		if (typeof(_this.initPro) == 'function') _this.initPro();
		if (typeof(_this.initAddons) == 'function') _this.initAddons();
		
		//_this.initHistoryTable();
		
		_this.waicWaitPreviewLoad = false;
		setTimeout(function() {_this.getPreviewAjax();}, 500);
	}
	
	ChatbotAdminPage.prototype.eventsChatbotAdminPage = function () {
		var _this = this.$obj;
		
		_this.content.find('.wbw-tab-content').on('waic-tab-change', function() {
			var tabId = $(this).attr('id');
			if (tabId == 'content-tab-history') {
				_this.preview.addClass('wbw-hidden');
				_this.mainButtons.addClass('wbw-hidden');
				if (!_this.historyTableObj) _this.initHistoryTable();
			} /*else if (tabId == 'content-tab-tools') {
				_this.preview.addClass('wbw-hidden');
			}*/ else {
				_this.preview.removeClass('wbw-hidden');
				_this.mainButtons.removeClass('wbw-hidden');
			}
		});
		
		_this.content.find('.waic-tools-groups .wbw-ws-block:not(.wbw-ws-disabled)').on('click', function(e) {
			e.preventDefault();
			var $btn = $(this),
				grId = $btn.attr('data-tool'),
				$tools = _this.content.find('.waic-tools-content');
			$tools.find('.waic-tool-content').removeClass('active');
			$tools.find('.waic-tool-content[data-tool="'+grId+'"]').addClass('active');
			$btn.closest('.waic-tools-groups').find('.wbw-ws-block').removeClass('current');
			$btn.addClass('current');
		});
		
		if (_this.taskNameWrapper.length) {
			_this.nameEditBtn = $('<i class="fa fa-fw fa-pencil waic-name-edit-btn">');
			_this.taskNameWrapper.parent().append(_this.nameEditBtn);
			_this.nameEditBtn.on('click', function() {
				_this.showEditNameDialog();
			});
		}
		_this.content.find('.waic-grbtn button').on('click', function(e) {
			e.preventDefault();
			var $btn = $(this),
				$grBtns = $btn.closest('.waic-grbtn'),
				grName = $grBtns.attr('data-name'),
				grValue = $btn.attr('data-value'),
				data = _this.content.find('.wbw-settings-field[data-group="'+grName+'"]');
			data.addClass('waic-group-hidden');
			data.filter('[data-group-value="'+grValue+'"]').removeClass('waic-group-hidden');
			$grBtns.find('button').removeClass('current');
			$btn.addClass('current');
		});
		
		_this.content.find('.waic-add-list-block').on('click', function(e) {
			e.preventDefault();
			var $btn = $(this),
				$wrapper = _this.content.find('.waic-list-blocks[data-block="' + $btn.attr('data-block') +'"]');
			if ($wrapper.length == 1) {
				var $tmp = $wrapper.find('.waic-list-blocks-tmp'),
					nextN = $tmp.attr('data-next-n'),
					$block = $tmp.clone().removeAttr('id').removeClass('wbw-hidden waic-list-blocks-tmp').removeAttr('data-next-n');
				if (typeof(nextN) == 'undefined') nextN = 0;
				else nextN = parseInt(nextN);
				$block.find('select, input').each(function() {
					var $field = $(this);
					$field.attr('name', $field.attr('name').replace('$n', nextN)); 
					$field.removeClass('wbw-nosave');
				});
				$tmp.attr('data-next-n', nextN + 1);
				$wrapper.append($block);
				$wrapper.closest('.wbw-settings-form').removeClass('wbw-hidden');
			}
			$btn.blur();
		});
		_this.content.find('.waic-list-blocks').on('click', '.wbw-elem-remove', function(e){
			e.preventDefault();
			var $btn = $(this),
				$wrapper = $(this).closest('.waic-list-blocks');
			$btn.closest('.waic-list-block').remove();
			if ($wrapper.find('.waic-list-block:not(.wbw-hidden)').length == 0) {
				$wrapper.closest('.wbw-settings-form').addClass('wbw-hidden');
				$wrapper.find('.waic-list-blocks-tmp').attr('data-next-n', 0);
			}
		});
		
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
		_this.content.find('button.wbw-button-upload').off('click').on('click', function (e) {
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
						$button.parent().addClass('wbw-hidden');
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
								$button.parent().addClass('wbw-hidden');
							}
						}
					}
				}
			};
			wp.media.editor.open($button);
			return false;
		});
		_this.content.on('change waic-update', 'select, input, textarea', function(e) {
			if(jQuery(this).closest('div[data-no-preview="1"]').length == 0) {
			   _this.getPreviewAjax();
			}
		});
		_this.content.find('.waic-reset-appearance').on('click', function(e) {
			e.preventDefault();
			waicShowConfirm(waicCheckSettings(_this.langSettings, 'reset-appearance'), 'waicChatbotAdminPage', 'resetAppearance', $(this));
			return false;
		});
		
		_this.mainButton.on('click', function(e){
			e.preventDefault();
			if (typeof(_this.beforeSaveAddon) == 'function') _this.beforeSaveAddon();
			if (typeof(_this.beforeSavePro) == 'function') _this.beforeSavePro();
			
			var $btn = $(this);
			$.sendFormWaic({
				elem: $btn,
				data: {
					mod: 'chatbots',
					action: 'saveChatbot',
					task_id: _this.taskId,
					params: jsonInputsWaic(_this.createForm, true),
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
		
		_this.preview.find('.waic-grbtn button').on('click', function(e) {
			e.preventDefault();
			var $btn = $(this);
			$btn.closest('.waic-grbtn').find('button').removeClass('current');
			$btn.addClass('current');
			_this.getPreviewAjax();
		});
		_this.preview.find('.waic-reset-preview').on('click', function(e) {
			e.preventDefault();
			var $btn = $(this);
			$.sendFormWaic({
				btn: $btn,
				data: {
					mod: 'chatbots',
					action: 'resetChatbotAdmin',
					task_id: _this.taskId,
				},
				onComplete: function(res) {
					_this.getPreviewAjax();
				}
			});
		});
		waicInitColorPicker();
	}
	ChatbotAdminPage.prototype.showLogDialog = function() {
		var _this = this.$obj;
		
		if (!_this.logDialog) {
			_this.logDialogWrapper = $('#waicLogDialog');
			_this.logDialog = _this.logDialogWrapper.removeClass('wbw-hidden').dialog({
				modal: true,
				autoOpen: false,
				position: {my: 'center', at: 'center', of: window},
				width: '90%',
				minHeight: 550,
				maxHeight: 650,
				dialogClass: "wbw-plugin",
				buttons: [
					{
						text: waicCheckSettings(_this.langSettings, 'close-btn'),
						class: 'wbw-button wbw-button-form wbw-button-minor',
						click: function() {
							jQuery(this).dialog('close');
						}
					}
				],
				open: function() {
					var $row = _this.currentLogRow;
					_this.logDialogWrapper.find('#waicLogDate').html($row.find('.waic-log-dd').html());
					_this.logDialogWrapper.find('#waicLogMode').html($row.find('.waic-log-mode').html());
					_this.logDialogWrapper.find('#waicLogUser').html($row.find('.waic-log-user').html());
					_this.logDialogWrapper.find('#waicLogIP').html($row.find('.waic-log-ip').html());
					_this.logDialogWrapper.find('#waicLogTokens').html($row.find('.waic-log-tokens').html());
					_this.logDialogWrapper.find('#waicLogMessages').html('<div class="waic-loader"><div class="waic-loader-bar bar1"></div><div class="waic-loader-bar bar2"></div></div>');
					_this.logDialogWrapper.parent().find('.ui-dialog-buttonset button').removeClass('ui-button ui-corner-all ui-widget');
					_this.getLogData();
				}
			});
		}
		_this.logDialog.dialog('open');
	}
	ChatbotAdminPage.prototype.resetAppearance = function($btn) {
		var _this = this.$obj;
		_this.waicWaitPreviewLoad = true;
		_this.waicNeedPreview = false;
		_this.content.find('#content-tab-appearance').find('select, input').each(function() {
			var $elem = $(this);
			if ($elem.is('input')) {
				var $wrap = $elem.closest('.waic-media-wrap');
				if ($wrap.length) {
					$wrap.find('.waic-gallery-element:not(.waic-gallery-media)').eq(0).trigger('click');
				} else {
					$elem.val('');
					if ($elem.hasClass('wbw-color-input')) $elem.trigger('waic-color-change');
				}
			}
			if ($elem.is('select')) {
				$elem.val($elem.find('option:first').val());
			}
		});
		
		
		_this.waicWaitPreviewLoad = false;
		_this.waicNeedPreview = true;
		setTimeout(function() {_this.getPreviewAjax();}, 500);
	}
	ChatbotAdminPage.prototype.getLogData = function() {
		var _this = this.$obj,
			$row = _this.currentLogRow;

		$.sendFormWaic({
			data: {
				mod: 'chatbots',
				action: 'getLogData',
				params: {
					task_id: _this.taskId,
					user_id: $row.find('.waic-log-user').attr('data-value'),
					ip: $row.find('.waic-log-ip').attr('data-value'),
					mode: $row.find('.waic-log-mode').attr('data-value'),
					dd: $row.find('.waic-log-dd').attr('data-value')
				}
			},
			onSuccess: function(res) {
				if(!res.error) {
					_this.logDialogWrapper.find('#waicLogMessages').html(res.html);
				}
			}
		});
	}
	ChatbotAdminPage.prototype.initHistoryTable = function () {
		var _this = this.$obj,
			url = typeof(ajaxurl) == 'undefined' || typeof(ajaxurl) !== 'string' ? WAIC_DATA.ajaxurl : ajaxurl,
			strPerPage = ' ' + waicCheckSettings(_this.langSettings, 'lengthMenu');
		$.fn.dataTable.ext.classes.sPageButton = 'button button-small wbw-paginate';
		
		_this.historyTableObj = _this.historyTable.DataTable({
			serverSide: true,
			processing: true,
			ajax: {
				'url': url + '?mod=chatbots&action=getHistoryPage&pl=waic&reqType=ajax&waicNonce=' + WAIC_DATA.waicNonce,
				'type': 'POST',
				data: function (d) {
					d.task_id = _this.taskId;
				},
			},
			search: true,
			lengthChange: true,
			lengthMenu: [ [10, 100, -1], [10 + strPerPage, 100 + strPerPage, "All"] ],
			paging: true,
			dom: 'Br<"pull-right"f>t<"waic-table-pages"pl>',
			responsive: {details: {display: $.fn.dataTable.Responsive.display.childRowImmediate, type: ''}},
			autoWidth: false,
			columnDefs: [
				{
					width: "40px",
					targets: [6,7]
				},
				{
					className: "dt-left",
					targets: [1,2]
				},
				{
					className: "dt-right",
					targets: [4,6]
				},
				{
					"orderable": false,
					targets: [7]
				}
			],
			order: [[ 0, 'desc' ]],
			language: {
				emptyTable: waicCheckSettings(_this.langSettings, 'emptyTable'),
				loadingRecords: '<div class="waic-leer-mini"></div>',
				paginate: {
					next: waicCheckSettings(_this.langSettings, 'pageNext'),
					previous: waicCheckSettings(_this.langSettings, 'pagePrev'),
					last: '<i class="fa fa-fw fa-angle-right">',
					first: '<i class="fa fa-fw fa-angle-left">'  
				},
				lengthMenu: ' _MENU_',
				search: '_INPUT_',
				processing: '<div class="waic-loader"><div class="waic-loader-bar bar1"></div><div class="waic-loader-bar bar2"></div></div>',
			},
			preDrawCallback: function (settings, json) {
				$('#waicHistoryTable_wrapper .dt-processing').css('top', '72px');
				$('#waicHistoryTable_wrapper .dt-processing > div:not(.waic-loader)').css('display', 'none');
			},
			drawCallback: function() {
				setTimeout(function () {
					$('#waicHistoryTable_wrapper .dt-paging')[0].style.display = $('#waicHistoryTable_wrapper .dt-paging button').length > 5 ? 'block' : 'none';
				}, 50);
			}
		});
		_this.historyTable.on('click', '.waic-history-log', function(e) {
			e.preventDefault();
			var $row = $(this).closest('tr');
			if ($row.hasClass('child')) {
				$row = $(_this.historyTableObj.row($row.attr('data-dt-row')).node());
			}
			_this.currentLogRow = $row.clone();
			_this.showLogDialog();
			$(this).blur();
			return false;
		});
	}

	ChatbotAdminPage.prototype.showEditNameDialog = function () {
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

	ChatbotAdminPage.prototype.getPreviewAjax = (function (wait) {
		var _this = this.$obj;
		if (_this.waicWaitPreviewLoad) return;

		if (_this.waicWaitResponse) {
			if(!_this.waicNeedPreview || wait) {
				_this.waicNeedPreview = true;
				setTimeout(function() {	_this.getPreviewAjax(true); }, 2000);
			}
			return;
		}
		_this.waicWaitResponse = true;
		_this.waicNeedPreview = false;
		
		$.sendFormWaic({
			data: {
				mod: 'chatbots',
				action: 'getChatbotAjax',
				task_id: _this.taskId,
				mobile: _this.preview.find('.waic-grbtn button.current').attr('data-value'),
				params: jsonInputsWaic(_this.createForm, true),
			},
			onSuccess: function(res) {
				if(!res.error) {
					_this.previewBlock.html(res.html);
					app.aiwuChatbot.init(_this.previewBlock);
				}
			},
			onComplete: function(res) {
				_this.waicWaitResponse = false;
			}
		});
	});
	
	app.waicChatbotAdminPage = new ChatbotAdminPage();

	$(document).ready(function () {
		app.waicChatbotAdminPage.init();
	});

}(window.jQuery, window));
