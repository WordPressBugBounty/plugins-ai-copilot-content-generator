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
		
		_this.schemeColor = _this.content.find('#waicSchemeColor');
		_this.historyWrapper = _this.content.find('.wbw-body-history');
		_this.historyTable = _this.historyWrapper.find('#waicHistoryTable');
		_this.logViewer = _this.historyWrapper.find('.wbw-log-viewer');
		_this.logTable = _this.historyWrapper.find('.wbw-table-list');
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
				_this.logViewer.addClass('wbw-hidden');
				_this.logTable.removeClass('wbw-hidden');
				if (!_this.historyTableObj) _this.initHistoryTable();
			} else if (tabId == 'content-tab-knowledge') {
				_this.preview.addClass('wbw-hidden');
				//_this.mainButtons.addClass('wbw-hidden');
			} else {
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
	
		_this.content.find('.waic-btn-scheme').on('click', function(e){
			e.preventDefault();
			_this.schemeColor.val($(this).attr('data-value')).trigger('change');
			return false;
		});
		_this.schemeColor.on('change', function(e){
			e.preventDefault();
			var $this = $(this),
				color = $this.val();
			_this.content.find('.waic-sheme-wrapper .waic-btn-scheme').each(function() {
				var $btn = $(this);
				if ($btn.attr('data-value') == color) $btn.addClass('active');
				else $btn.removeClass('active');
			});
		});
		_this.content.find('.waic-accordion-link').on('click', function(e){
			e.preventDefault();
			var $this = $(this),
				$content = _this.content.find($this.attr('data-content'));
			if ($content && $content.length) {
				if ($this.hasClass('collapsed')) {
					$this.removeClass('collapsed');
					$content.removeClass('wbw-hidden');
				} else {
					$this.addClass('collapsed');
					$content.addClass('wbw-hidden');
				}
			}
			return false;
		});
		_this.content.find('#waicHistoryExport').on('click', function(e){
			e.preventDefault();
			_this.showExportDialog();
			return false;
		});
	}
	ChatbotAdminPage.prototype.showExportDialog = function () {
		var _this = this.$obj;
	
		if (!_this.exportDialog) {
			_this.exportDialog = $('#waicExportDialog').removeClass('wbw-hidden').dialog({
				modal: true,
				autoOpen: false,
				position: {my: 'center', at: 'center', of: window},
				width: '500px',
				dialogClass: "wbw-plugin",
				buttons: [
					{
						text: waicCheckSettings(_this.langSettings, 'save-btn'),
						class: 'wbw-button wbw-button-form wbw-button-main',
						click: function(e) {
							var $inputs = _this.exportDialog.find('.wbw-nosave');
							$inputs.removeClass('wbw-nosave');
							$.sendFormWaic({
								btn: this,
								data: {
									mod: 'chatbots',
									action: 'exportLog',
									params: jsonInputsWaic(_this.exportDialog, true),
								},
								onSuccess: function(res) {
									if (!res.error) {
										var blob = new Blob(
											[ res.data.file ],
											{ type: 'text/json' }
										);
										var fileName = 'aiwu_export.json',
											link = document.createElement('a');
											link.href = window.URL.createObjectURL(blob);
											link.download = fileName;
										link.click();
										link.remove();
									}
								}
							});
							$inputs.addClass('wbw-nosave');
			
							$(this).dialog('close');
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
					_this.exportDialog.find('#waicExportTask').val(_this.content.find('#waicHistoryChatbots').val());
				},
			});
		}
		_this.exportDialog.dialog('open');
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
	ChatbotAdminPage.prototype.getLogData = function( data ) {
		var _this = this.$obj,
			$row = _this.currentLogRow;

		$.sendFormWaic({
			data: {
				mod: 'chatbots',
				action: 'getLogData',
				params: data,
			},
			onSuccess: function(res) {
				if(!res.error) {
					_this.logViewer.find('.waic-right-panel .waic-loader').addClass('wbw-hidden');
					var $container = _this.logViewer.find('.waic-right-panel .waic-panel-body'),
						search = $('#waicHistorySearch').val();
					$container.html(res.html);
					if (search.length) {
						search = search.toLowerCase();
						var $target = $container.find('.waic-message-text').filter(function() { 
								return $(this).text().toLowerCase().includes(search); 
							}).first(); 
						if ($target.length) {
							var offsetTop = $target.position().top + $container.scrollTop();
							$container.animate({ scrollTop: offsetTop }, 500); 
						}
						
					}
				}
			}
		});
	}
	ChatbotAdminPage.prototype.initHistoryTable = function () {
		var _this = this.$obj,
			url = typeof(ajaxurl) == 'undefined' || typeof(ajaxurl) !== 'string' ? WAIC_DATA.ajaxurl : ajaxurl,
			strPerPage = ' ' + waicCheckSettings(_this.langSettings, 'lengthMenu');
		$.fn.dataTable.ext.classes.sPageButton = 'button button-small wbw-paginate';
		$.fn.DataTable.ext.pager.numbers_length = 5;
		
		_this.historyTableObj = _this.historyTable.DataTable({
			serverSide: true,
			processing: true,
			ajax: {
				'url': url + '?mod=chatbots&action=getHistoryPage&pl=waic&reqType=ajax&waicNonce=' + WAIC_DATA.waicNonce,
				'type': 'POST',
				data: function (d) {
					d.task_id = $('#waicHistoryChatbots').val();
					d.search = $('#waicHistorySearch').val();
				},
				 beforeSend: function (jqXHR, settings) {
					if (!_this.logViewer.hasClass('wbw-hidden')) {
						_this.logViewer.find('.waic-left-panel .waic-loader').removeClass('wbw-hidden');
						_this.logViewer.find('.waic-right-panel').removeClass('view');
					}
				}
			},
			search: true,
			lengthChange: true,
			lengthMenu: [ [10, 100, -1], [10 + strPerPage, 100 + strPerPage, "All"] ],
			paging: true,
			//dom: 'Br<"pull-right"f>t<"waic-table-pages"pl>',
			dom: 'Brt<"waic-table-pages"pl>',
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
				$('#waicHistoryTable_wrapper .dt-processing').css('top', '35px');
				$('#waicHistoryTable_wrapper .dt-processing > div:not(.waic-loader)').css('display', 'none');
			},
			drawCallback: function() {
				setTimeout(function () {
					$('#waicHistoryTable_wrapper .dt-paging')[0].style.display = $('#waicHistoryTable_wrapper .dt-paging button').length > 5 ? 'block' : 'none';
					if (!_this.logViewer.hasClass('wbw-hidden')) {
						_this.setHistoryLog(0);
						_this.logViewer.find('.waic-left-panel .waic-loader').addClass('wbw-hidden');
					}
				}, 50);
			}
		});
		$('#waicHistoryChatbots').on('change', function(e) {
			_this.historyTableObj.ajax.reload();
		});
		var timerSearch;
		$('#waicHistorySearch').on('input', function(e) {
			clearTimeout(timerSearch);
			timerSearch = setTimeout(function() { 
				_this.historyTableObj.ajax.reload();
			}, 1000);
		});
		
		_this.historyTable.on('click', '.waic-history-log', function(e) {
			e.preventDefault();
			_this.logViewer.removeClass('wbw-hidden');
			_this.logTable.addClass('wbw-hidden');
			_this.setHistoryLog($(this).closest('.waic-history-log').attr('data-num'));
			$(this).blur();
			return false;
		});
	}
	ChatbotAdminPage.prototype.setHistoryLog = function ( num ) {
		var _this = this.$obj,
			cntRows = waicChatbotAdminPage.historyTableObj.rows().count(),
			$list = _this.logViewer.find('.waic-log-list').html(''),
			allChatbots = $('#waicHistoryChatbots').val().length == 0,
			strTokens = waicCheckSettings(_this.langSettings, 'tokens'),
			strCount = waicCheckSettings(_this.langSettings, 'count');
		if (allChatbots) {
			var chatNames = {};
			$('#waicHistoryChatbots option').each(function() {
				var $this = $(this),
					id = $this.val();
				if (id.length) chatNames[id] = $this.html();
			});
		} 
		for (var i = 0; i < cntRows; i++) {
			var row = waicChatbotAdminPage.historyTableObj.row(i).data(),
				$view = $(row[7]),
				$elem = $('<div class="waic-log-elem"></div>'),
				found = $view.attr('data-found'),
				data = $view.attr('data-log'),
				dataArr = JSON.parse(data);
			
			$elem.attr('data-log', data).attr('data-tokens', row[4]);
			$elem.append($('<div class="waic-log-header"><div class="waic-log-user">' + row[1] + '</div><div class="waic-log-dd">' + row[0] + '</div></div>'));
			if (found) {
				found = found.replaceAll('{{', '<b>').replaceAll('}}', '</b>');
				$elem.append($('<div class="waic-log-found">' + found + '</div>'));
			} else {
				$elem.append($('<div class="waic-log-body"><div class="waic-log-data"><div class="waic-log-name">' + strCount + '</div>: ' + row[6] + '</div><div class="waic-log-data"><div class="waic-log-name">' + strTokens + '</div>: ' + row[4] + '</div></div>'));
			}
			if (allChatbots && dataArr.task_id in chatNames) {
				$elem.append($('<div class="waic-log-chatbot">' + chatNames[dataArr.task_id] + '</div>'));
			}
			if (i == num) {
				$elem.addClass('current');
			}
			$list.append($elem);
		}
		var $mainPaging = $('#waicHistoryTable_wrapper .dt-paging');
		_this.logViewer.find('.waic-table-pages').html($mainPaging.clone());
		_this.logViewer.find('.waic-table-pages .dt-paging-button').on('click', function(e) {
			e.preventDefault();
			$mainPaging.find('.dt-paging-button[data-dt-idx="' + $(this).attr('data-dt-idx') + '"]').trigger('click');
		});
		
		var $panelLeft = _this.logViewer.find('.waic-left-panel'),
			$panelRight = _this.logViewer.find('.waic-right-panel');
		$panelRight.removeClass('view');
		
		$list.find('.waic-log-elem').on('click', function(e) {
			e.preventDefault();
			var $this = $(this),
				data = JSON.parse($this.attr('data-log'));
			if (data) {
				$list.find('.waic-log-elem').removeClass('current');
				$this.addClass('current');
				$panelRight.find('.waic-loader').removeClass('wbw-hidden');
				if (_this.logViewer.hasClass('waic-full-panel')) {
					$panelRight.addClass('view');
				}
				$panelRight.find('.waic-panel-body').html('');
				var $header = $panelRight.find('.waic-panel-header');
				$header.find('.waic-log-user').html($this.find('.waic-log-user').html());
				$header.find('.waic-log-ip').html('(' + data.ip + ')');
				$header.find('.waic-log-tokens').html(waicCheckSettings(_this.langSettings, 'tokens') + ': ' + $this.attr('data-tokens'));
				_this.getLogData(data);
			}
		});
		_this.logViewer.removeClass('waic-full-panel');
		var windowWidth = $(window).width(),
			panelWidth = $panelLeft.outerWidth() + $panelRight.outerWidth(); 
		if (panelWidth < windowWidth) { 
			_this.logViewer.removeClass('waic-full-panel');
			var $target = $list.find('.waic-log-elem.current');
			if ($target.length) {
				var $container = $target.closest('.waic-panel-body'),
					offsetTop = $target.position().top + $container.scrollTop();
				$container.animate({ scrollTop: offsetTop }, 500); 
				$target.trigger('click');
			}
		} else { 
			_this.logViewer.addClass('waic-full-panel');
			_this.logViewer.find('.waic-panel-hidden').on('click', function(e) {
				e.preventDefault();
				$panelRight.removeClass('view');
			});
		}
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
						text: waicCheckSettings(_this.langSettings, 'save-btn'),
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
