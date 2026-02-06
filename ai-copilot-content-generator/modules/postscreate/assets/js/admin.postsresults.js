(function ($, app) {
"use strict";
	function PostsResultsPage() {
		this.$obj = this;
		return this.$obj;
	}
	
	PostsResultsPage.prototype.init = function () {
		var _this = this.$obj;
		_this.isRefreshing = false;
		_this.isRefreshWating = false;
		_this.lastRefreshing = false;
		_this.refreshTimer = 10000;
		_this.isPro = WAIC_DATA.isPro == '1';
		_this.langSettings = waicParseJSON($('#waicLangSettingsJson').val());
		_this.taskStatuses = waicParseJSON($('#waicTaskStatusesJson').val());
		_this.taskActions = waicParseJSON($('#waicTaskActionsJson').val());
		_this.content = $('.wbw-container');
		_this.taskId = _this.content.find('#waicTaskId').val();
		_this.stopStartButton = _this.content.find('#waicTaskStopStart');
		_this.saveButton = _this.content.find('#waicTaskSave');
		_this.backButton = _this.content.find('#waicTaskCansel');
		_this.taskStatusBlock = _this.content.find('.waic-status-block');
		
		
		_this.tableResults = _this.content.find('.waic-task-results');
		_this.postWrapper = _this.tableResults;
		_this.isBulkMode = _this.tableResults.attr('data-bulk') == '1';
		
		_this.taskStatus = _this.taskStatusBlock.attr('data-status');
		_this.progressBarBlock = _this.content.find('.waic-progressbar-block');
		_this.progressBar = _this.content.find('.waic-progressbar span');
		_this.complitedBlock = _this.content.find('.waic-complited-points');
		_this.complitedPoints = _this.complitedBlock.text();
		_this.editBodyDialog = false;
		_this.actionPostIds = false;
		
		_this.eventsPostsResultsPage();
		
		if (typeof(_this.initBulk) == 'function') {
			_this.initBulk();
		} else {
			_this.isBulkMode = false;
		}
		
		_this.firstLoad = true;
		_this.taskResultsInit();
		if (typeof(_this.initAddons) == 'function') _this.initAddons();
	}
	
	PostsResultsPage.prototype.eventsPostsResultsPage = function () {
		var _this = this.$obj;
		_this.activeButton = false;
		_this.stopStartButton.on('click', function(e){
			e.preventDefault();
			_this.activeButton = $(this);
			_this.doActionTask(0);
			return false;
		});
		_this.saveButton.on('click', function(e){
			e.preventDefault();
			_this.activeButton = $(this);
			_this.doActionTask(0);
			return false;
		});
		_this.backButton.click(function(e){
			e.preventDefault();
			waicShowConfirm(waicCheckSettings(_this.langSettings, 'confirm-back'), 'waicPostsResultsPage', 'doBackAction', $(this));
			return false;
		});
	}
	PostsResultsPage.prototype.showBodySectionDialog = function () {
		var _this = this.$obj,
			timer = 0;
		if (!_this.editBodyDialog) {
			//$wrapper = $('#waicBodyResultsEditor');
			_this.editBodyDialog = $('#waicBodyResultsEditor').removeClass('wbw-hidden').dialog({
				modal: true,
				autoOpen: false,
				position: {my: 'center', at: 'center', of: window},
				width: '700px',
				//width: '90%',
				dialogClass: "wbw-plugin",
				buttons: [
					{
						text: waicCheckSettings(_this.langSettings, 'save-btn'),
						class: 'wbw-button wbw-button-form wbw-button-main',
						click: function() {
							jQuery(this).dialog('close');
							var value = waicGetTxtEditorVal('waicBodySectionEditor'),
								$wrapper = _this.activeBodySection.closest('.waic-editable'),
								$input = $wrapper.find('input');
							_this.activeBodySection.html(value);
							if ($input.length) {
								$input.val(value);
								$wrapper.addClass('edited');
							}
						},
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
					jQuery(this).parent().find('.ui-dialog-buttonset button').removeClass('ui-button ui-corner-all ui-widget');
				}
			});
			var $field = $('#waicBodyResultsEditor #waicBodySectionEditor'),
				fieldId = $field.attr('id'),
				editorSettings = {
					selector: '#' + fieldId,
					mediaButtons: true,
					quicktags: true,
					tinymce: {
						wpautop: true,
						toolbar1: 'formatselect,bold,italic,bullist,numlist,blockquote,alignleft,aligncenter,alignright,forecolor,link,undo,redo,wp_help',
						height: 200
					}
				};
			wp.editor.remove(fieldId);
			wp.editor.initialize(fieldId, editorSettings);
			timer = 100;
			
			//_this.tinyMCE = window.tinyMCE.get('#waicBodySectionEditor'); 
		}
		//let content = window.tinyMCE.get('waicBodySectionEditor');
		//window.tinyMCE.get('waicBodySectionEditor').setContent('fddgdfgdg');
		
		//window.tinyMCE.get('waicBodySectionEditor').setContent(_this.activeBodySection.html());
		//window.tinyMCE.triggerSave();
		waicSetTxtEditorVal('waicBodySectionEditor', _this.activeBodySection.html());
		//_this.editBodyDialog.dialog('open');
		setTimeout(function () {
			_this.editBodyDialog.dialog('open');
		}, timer);
	}
	PostsResultsPage.prototype.taskResultsInit = function () {
		var _this = this.$obj;
		_this.stopStartButton.prop('disabled', 'disabled');
		if (_this.taskActions.start) {
			_this.stopStartButton.text(waicCheckSettings(_this.langSettings, 'start')).attr('data-action', 'start').prop('disabled', '');
		} else if (_this.taskActions.stop) {
			_this.stopStartButton.text(waicCheckSettings(_this.langSettings, 'stop')).attr('data-action', 'stop').prop('disabled', '');
		}
		_this.backButton.prop('disabled', _this.taskActions.cancel ? '' : 'disabled');
		_this.saveButton.prop('disabled', _this.taskActions.save ? '' : 'disabled');
		_this.progressBar.animate({width: _this.complitedPoints + '%'}, 1000);
		
		if (_this.taskActions.published) _this.progressBarBlock.addClass('wbw-hidden');
		else _this.progressBarBlock.removeClass('wbw-hidden');
		
		/*var isNeed = typeof($postWrapper) == 'undefined' ? false : true;
		if (!_this.isBulkMode || isNeed) {
			_this.postResultsInit();
			if (_this.isBulkMode) _this.taskResultsInitPro();
		}*/
		_this.postResultsInit();
		if (_this.isBulkMode) _this.postResultsInitBulk();
		_this.setRefreshTimer();
	}
		
	PostsResultsPage.prototype.postResultsInit = function () {
		var _this = this.$obj;
		if (!_this.postWrapper) return false;

		_this.postWrapper.find('input, select, textarea').prop('disabled', 'disabled');
		_this.postWrapper.find('.wbw-refresh-button, .waic-results-section-body').addClass('disabled');
		if (_this.taskActions.update) {
			_this.postWrapper.find('.waic-post-results[data-can-update="1"]').find('input.waic-results-1, select:not(.waic-results-0), textarea.waic-results-1').prop('disabled', '');
			_this.postWrapper.find('.waic-post-results[data-can-update="1"]').find('.wbw-refresh-button, .waic-results-section-body.waic-results-1').removeClass('disabled');
		}
		_this.postWrapper.find('.wbw-refresh-button:not(.waic-invisible)').on('click', function(e) {
			e.preventDefault();
			var $btn = $(this);
			if ($btn.hasClass('active')) $btn.removeClass('active');
			else $btn.addClass('active');
		});
		_this.postWrapper.find('input, select, textarea').on('change', function() {
			var $input = $(this),
				$editable = $input.closest('.waic-editable');
			if ($editable.length) $editable.addClass('edited');
		});
		_this.postWrapper.find('.waic-publish-button').prop('disabled', 'disabled');
		_this.postWrapper.find('.waic-results-sections .wbw-elem-action').addClass('disabled');

		if (_this.taskActions.update) {
			_this.postWrapper.find('.waic-results-sections.waic-results-1 .wbw-elem-action').removeClass('disabled');
			_this.postWrapper.find('.waic-post-results[data-can-update="1"] .waic-results-sections.waic-results-1').each(function() {
				var $resultsSectionsWrapper = $(this);
				$resultsSectionsWrapper.on('click', '.wbw-elem-remove', function(e){
					e.preventDefault();
					$(this).closest('.wbw-settings-field').remove();
					_this.resultSectionsRenum($resultsSectionsWrapper);
					$resultsSectionsWrapper.closest('.waic-editable').addClass('edited');
				});
				$resultsSectionsWrapper.sortable({
					cursor: "move",
					axis: "y",
					placeholder: "sortable-placeholder",
					stop: function (e, ui) {
						_this.resultSectionsRenum($resultsSectionsWrapper);
						$resultsSectionsWrapper.closest('.waic-editable').addClass('edited');
					},
				});
			});
			
			_this.postWrapper.find('.waic-post-results[data-can-update="1"] .waic-results-section-body:not(.disabled)').on('click', function(e){
				e.preventDefault();
				_this.activeBodySection = $(this);
				_this.showBodySectionDialog();
			});
			_this.postWrapper.find('.waic-post-results[data-can-publish="1"] .waic-publish-button').prop('disabled', '');
			_this.postWrapper.find('.waic-post-results[data-can-publish="1"] .waic-publish-button').on('click', function(e){
				e.preventDefault();
				_this.activeButton = $(this);
				_this.doActionTask(0);
				return false;
			});
		}
		
		waicInitDatePicker(_this.postWrapper);
		_this.postWrapper.find('select.wbw-chosen.no-chosen').removeClass('no-chosen');
		waicInitMultySelects(_this.postWrapper);
	}
	
	PostsResultsPage.prototype.doActionTask = function (force, action) {
		var _this = this.$obj,
			$btn = _this.activeButton,
			action = typeof(action) == 'undefined' ? $btn.attr('data-action') : action,
			param = $btn.attr('data-param'),
			mod = $btn.attr('data-mod'),
			refresh = {},
			edited = {},
			publish = [],
			deleted = [];
		if (action == 'publish') {
			if (_this.actionPostIds) publish = _this.actionPostIds;
			else if ($btn.attr('data-post')) publish.push($btn.attr('data-post'));
			else publish.push($btn.closest('.waic-post-results').attr('data-post'));
		}
		if (action == 'delete' || action == 'cancel') {
			if (_this.actionPostIds) deleted = _this.actionPostIds;
			else if ($btn.attr('data-post')) deleted.push($btn.attr('data-post'));
		}
		if (_this.postWrapper) {
			_this.postWrapper.find('.wbw-refresh-button.active:not(.disabled)').each(function() {
				var $btn = $(this),
					$wrapper = $btn.closest('.waic-field-block'),
					refreshNum = $wrapper.attr('data-refresh-num'),
					refreshBlock = $wrapper.attr('data-block'),
					$param = $wrapper.find('.waic-refresh-data'),
					pcId = $btn.closest('.waic-post-results').attr('data-post');
				if (!refresh[pcId]) refresh[pcId] = {};
				if (typeof(refreshNum) !== 'undefined') {
					if (!refresh[pcId][refreshBlock]) refresh[pcId][refreshBlock] = [];
					refresh[pcId][refreshBlock].push(refreshNum);
				} else {
					refresh[pcId][refreshBlock] = $param.length ? $param.val() : 0;
				}
			});

			_this.postWrapper.find('.waic-post-results').each(function() {
				var $block = $(this),
					$editedBlocks = $block.find('.waic-editable.edited');
				if ($editedBlocks.length) {
					var pcId = $block.attr('data-post');
					if (!edited[pcId]) edited[pcId] = {};
					edited[pcId] = jsonInputsWaic($editedBlocks);
				}
			});
		}
		_this.actionPostIds = false;

		if (!$btn.prop('disabled')) {
			$.sendFormWaic({
				elem: $btn,
				data: {
					mod: typeof(mod) == 'undefined' ? 'workspace' : mod,
					action: 'doActionTask',
					task_id: _this.taskId,
					task_action: action,
					param: param,
					force_action: force,
					refresh: refresh,
					edited: edited,
					publish: publish,
					deleted: deleted
				},
				onSuccess: function(res) {
					if (!res.error) {
						if (res.confirm) {
							waicShowConfirm(res.confirm, 'waicPostsResultsPage', 'doActionTask', 1);
						} else {
							if (!_this.taskActions.running) _this.getCurentResults($btn);
							if (_this.isBulkMode) _this.afterActionTaskBulk();
						}
					}
				}
			});
		}
	}
	PostsResultsPage.prototype.getCurentResults = function ($btn) {
		var _this = this.$obj;
		
		if (_this.isRefreshing) {
			if (!_this.isRefreshWating) {
				_this.isRefreshWating = true;
				setTimeout(function() {
					_this.getCurentResults();
				}, 100);
			}
			return false;
		}
		var nowTS = Date.now();
		if (_this.lastRefreshing + 2000 > nowTS) return false;
		_this.isRefreshWating = false;
		_this.lastRefreshing = nowTS;
			
		_this.isRefreshing = true;
		if (_this.isBulkMode) {
			_this.getCurentResultsBulk();
		} else {
			_this.getCurentPostResults($btn);
		}
	}
	PostsResultsPage.prototype.getCurentPostResults = function ($btn, $postId) {
		var _this = this.$obj,
			$postId = typeof($postId) == 'undefined' ? 0 : $postId;
		$.sendFormWaic({
			elem: $btn,
			data: {
				mod: 'workspace',
				action: 'getCurrentTaskResults',
				task_id: _this.taskId,
				post_id: $postId
			},
			onSuccess: function(res) {
				if (!res.error && res.data) {
					//var $table = $(res.data.table);
					if (res.data.table && _this.postWrapper) _this.postWrapper.html(res.data.table);
					if (_this.scrollBlock) {
						var $parentBlock = $('#waicPostResultsDialog'),
							$innerBlock = $('.waic-field-block[data-block="' + _this.scrollBlock + '"]');
						if ($parentBlock.length && $innerBlock.length) $parentBlock.scrollTop($parentBlock.scrollTop() + $innerBlock.position().top);
					}

					_this.setTaskData(res.data);
				}
			},
			onComplete: function (res) {
				_this.isRefreshing = false;
			},
		});
	}
	PostsResultsPage.prototype.setTaskData = function (data) {
		var _this = this.$obj,
			oldStatus = _this.taskStatus,
			task = data.task,
			message = ( task['status'] == 7 && task['message'].length ) ? '<img src="' + WAIC_DATA.imgPath + 'info.png" class="wbw-tooltip" title="' + task['message'] + '">' : '';

		_this.taskActions = data.actions;
		_this.taskStatus = task['status'];

		_this.taskStatusBlock.removeClass('waic-status-' + oldStatus).addClass('waic-status-' + _this.taskStatus).attr('data-status', _this.taskStatus).html(_this.taskStatuses[_this.taskStatus] + message);
		if (message.length) waicInitTooltips('.waic-status-block');
		_this.complitedPoints = Math.round(task['step'] * 10000 / (task['steps'] == 0 ? 1 : task['steps'])) / 100;
		_this.complitedBlock.text(_this.complitedPoints);
		_this.taskResultsInit();
	}
	PostsResultsPage.prototype.setRefreshTimer = function () {
		var _this = this.$obj;
		if (_this.taskActions.running) {
			setTimeout(function () {
				_this.getCurentResults();
			}, _this.refreshTimer);
		} else {
			if (_this.firstLoad) _this.getCurentResults();
			_this.firstLoad = false;
		}
	}
	
	PostsResultsPage.prototype.resultSectionsRenum = function ($wrapper) {
		var _this = this.$obj;
		$wrapper.find('.waic-list-number').each(function(index) {
			$(this).text(index+1);
		});
		
	}
	
	PostsResultsPage.prototype.doBackAction = function ($btn) {
		var _this = this.$obj;

		if (!$btn.prop('disabled')) {
			$.sendFormWaic({
				elem: $btn,
				data: {
					mod: 'workspace',
					action: 'doActionTask',
					task_id: _this.taskId,
					task_action: 'cancel'
				},
				onSuccess: function(res) {
					if (!res.error) {
						location.reload();
					}
				}
			});
		}
	}
	
	app.waicPostsResultsPage = new PostsResultsPage();

	$(document).ready(function () {
		app.waicPostsResultsPage.init();
	});

}(window.jQuery, window));