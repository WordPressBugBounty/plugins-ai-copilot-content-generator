(function ($, app) {
"use strict";
	function FormsAdminPage() {
		this.$obj = this;
		return this.$obj;
	}
	
	FormsAdminPage.prototype.init = function () {
		var _this = this.$obj;

		_this.isPro = WAIC_DATA.isPro == '1';

		_this.langSettings = waicParseJSON($('#waicLangSettingsJson').val());
		_this.content = $('.wbw-tabs-content');
		_this.template = $('.wbw-template');
		_this.createForm = _this.content.find('#waicFormCreateForm');
		_this.fieldsList = _this.content.find('#waicFieldsSection');
		_this.outputsList = _this.content.find('#waicOutputsSection');
		_this.submitsList = _this.content.find('#waicSubmitsSection');
		_this.resetsList = _this.content.find('#waicResetsSection');
		_this.rulesList = _this.content.find('#waicRulesSection');

		_this.mainButtons = _this.content.find('#waicMainButtons');
		_this.mainButton = _this.content.find('#waicSaveTask');
		_this.taskNameWrapper = $('#waicTaskNameWrapper');
		_this.taskId = _this.content.find('#waicPCId').val();
		
		_this.historyTable = _this.content.find('#waicHistoryTable');
		
		_this.eventsFormAdminPage();
		if (typeof(_this.initPro) == 'function') _this.initPro();
		if (typeof(_this.initAddons) == 'function') _this.initAddons();
	}
	FormsAdminPage.prototype.getUniqId = function ($block, $attr) {
		do {
			var uniq = Math.random().toString(36).substr(2, 9);
		} while ($block.find('['+$attr+'="'+uniq+'"]').length > 0);
		return uniq;
	}
	FormsAdminPage.prototype.addSection = function ($sections, field) {
		var _this = this.$obj,
			$section = _this.template.find('.wbw-section[data-section-key="'+field+'"]');
		if ($section.length) {
			var uniqId = _this.getUniqId($sections, 'data-section-id'),
				$newSection = $section.clone();
			$newSection.attr('data-section-id', uniqId);
			$newSection.find('[name]').each(function() {
				var $this = $(this);
				$this.attr('name', $this.attr('name').replace('#n#', uniqId));
			});
			$newSection.find('[data-parent-select]').each(function() {
				var $this = $(this);
				$this.attr('data-parent-select', $this.attr('data-parent-select').replace('#n#', uniqId));
			});
			$newSection.find('[data-parent-check]').each(function() {
				var $this = $(this);
				$this.attr('data-parent-check', $this.attr('data-parent-check').replace('#n#', uniqId));
			});
			
			$newSection.find('.wbw-section-label').html($newSection.attr('data-section-label')+'_'+uniqId);
			$newSection.find('.waic-dynamic-list').each(function() {
				var $this = $(this);
				_this.setSelectList($this, $this.attr('data-label'), $this.attr('data-custom'));
			});
			
			$newSection.find('.no-tooltip').addClass('wbw-tooltip').removeClass('no-tooltip');
			$newSection.find('.no-chosen').removeClass('no-chosen');
			$sections.append($newSection);
			$newSection.find('.waic-dynamic-list').trigger('waic-change');
			waicInitTooltips($newSection);
			waicInitMultySelects($newSection);
				
			$newSection.find('.wbw-section-toggle').click();
		}
	}
	FormsAdminPage.prototype.refreshDynamicLists = function (label, doTrigger) {
		var _this = this.$obj;
		_this.content.find('.wbw-sections-list .waic-dynamic-list[data-label*="'+label+'"]').each(function() {
			var $this = $(this);
			_this.setSelectList($this, $this.attr('data-label'), $this.attr('data-custom'));
			if (doTrigger) $this.trigger('waic-change');
		});
	}

	FormsAdminPage.prototype.setSelectList = function ($target, label, custom) {
		var _this = this.$obj,
			targetValue = $target.val(),
			labels = label.split(' '),
			found = false;
		if (typeof custom == 'undefined') custom = '';
		if (typeof targetValue == 'undefined' || targetValue == null) targetValue = custom;
		if (typeof targetValue == 'string') targetValue = [targetValue];
		$target.find('option').remove();
		for (var i = 0; i < labels.length; i++) {
			_this.content.find('.wbw-sections-list .wbw-section[data-section-label="'+labels[i]+'"]').each(function() {
				var $this = $(this),
					value = $this.find('.wbw-section-label').text(),
					$label = $this.find('.wbw-section-tlabel'),
					parts = value.split('_'),
					id = parts.length > 1 ? parts[1] : value;
				if (targetValue.indexOf(id) != -1) found = true;
				if ($label.length) {
					var l = $label.text();
					if (l.length) {
						value += ' ' + (l.length > 10 ? l.substring(0, 10) + '...' : l);
					}
				}
				$target.append($('<option>').val(id).text(value));
			});
		}
		if (custom != '') $target.append($('<option>').val('custom').text(custom));
		if (found) $target.val(targetValue);
		if ($target.attr('multiple')) $target.trigger("chosen:updated");
	}
	
	FormsAdminPage.prototype.eventsFormAdminPage = function () {
		var _this = this.$obj;
		
		_this.content.find('.wbw-tab-content').on('waic-tab-change', function() {
			var tabId = $(this).attr('id');
			if (tabId == 'content-tab-history') {
				_this.mainButtons.addClass('wbw-hidden');
				if (!_this.historyTableObj) _this.initHistoryTable();
			} else {
				_this.mainButtons.removeClass('wbw-hidden');
				if (tabId == 'content-tab-appearance') { 
				var cssText = _this.content.find('#waicCssEditor').get(0);
					if (typeof(cssText.CodeMirrorEditor) === 'undefined') {
						if (typeof(CodeMirror) !== 'undefined') {
							var cssEditor = CodeMirror.fromTextArea(cssText, {
								mode: 'css',
								lineWrapping: true,
								lineNumbers: true,
								matchBrackets: true,
								autoCloseBrackets: true
							});
							cssText.CodeMirrorEditor = cssEditor;
						}
					} else {
						cssText.CodeMirrorEditor.refresh();
					}
				}
			}
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
		_this.content.find('.wbw-sections-list').on('click', '.wbw-section-toggle', function(e){
			e.preventDefault();
			var el = $(this),
				i = el.find('i'),
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
		
		_this.content.find('#waicAddField').on('click', function(e) {
			e.preventDefault();
			var field = $('#waicFieldsForAdd').val();
			_this.addSection(_this.fieldsList, field);
			_this.refreshDynamicLists('FIELD');
			$(this).blur();
		});
		_this.content.find('#waicAddOutput').on('click', function(e) {
			e.preventDefault();
			_this.addSection(_this.outputsList, 'output');
			_this.refreshDynamicLists('OUTPUT');
			$(this).blur();
		});
		_this.content.find('#waicAddSubmit').on('click', function(e) {
			e.preventDefault();
			_this.addSection(_this.submitsList, 'submit');
			$(this).blur();
		});
		_this.content.find('#waicAddReset').on('click', function(e) {
			e.preventDefault();
			_this.addSection(_this.resetsList, 'reset');
			$(this).blur();
		});
		_this.content.find('#waicAddRule').on('click', function(e) {
			e.preventDefault();
			_this.addSection(_this.rulesList, 'rule');
			$(this).blur();
		});
		_this.rulesList.on('click', '.waic-add-rule-block', function(e) {
			e.preventDefault();
			var $btnBlock = $(this).closest('.waic-add-block'),
				$section = $btnBlock.closest('.wbw-section'),
				typ = $btnBlock.attr('data-type'), 
				$block = _this.template.find('.waic-rule-'+typ);
			if ($block.length) {
				var uniqId = $section.attr('data-section-id'),
					$newBlock = $block.clone(),
					$target = $section.find('.waic-rule-'+typ).last(),
					num = 0;
				if ($target.length == 0) $target = $btnBlock;
				else num = Number($target.attr('data-rule-num')) + 1;
				$newBlock.find('[name]').each(function() {
					var $this = $(this);
					$this.attr('name', $this.attr('name').replace('#n#', uniqId).replace('#m#', num));
				});
				$newBlock.find('.waic-dynamic-list').each(function() {
					var $this = $(this);
					_this.setSelectList($this, $this.attr('data-label'), $this.attr('data-custom'));
				});
				$newBlock.attr('data-rule-num', num);
				
				if ($target.length == 1) {
					$target.after($newBlock);
					//$newBlock.find('.waic-rule-operator').trigger('waic-change');
				}
			}
			$(this).blur();
		});
		_this.rulesList.on('change waic-change', '.waic-rule-operator', function() {
			var $this = $(this),
				value = $this.val(),
				$block = $this.closest('.waic-rule-block'),
				$value = $block.find('.waic-rule-value');
			if ($value) {
				if ($block.hasClass('waic-rule-if')) {
					if (value == 'empty' || value == 'not_empty') $value.addClass('wbw-hidden');
					else $value.removeClass('wbw-hidden');
				} else {
					if (value == 'value') $value.removeClass('wbw-hidden');
					else $value.addClass('wbw-hidden');
				}
			}
		});
		
		_this.rulesList.on('click', '.wbw-rule-delete', function() {
			$(this).closest('.waic-rule-block').remove();
		});
		
		_this.content.find('.wbw-sections-list').on('click', '.wbw-section-remove', function(e){
			e.preventDefault();
			var $section = $(this).closest('.wbw-section'),
				label = $section.attr('data-section-label');
			if ($section.length) $section.remove();
			_this.refreshDynamicLists(label, true);
		});
		_this.content.find('.wbw-sections-list').on('click', '.wbw-section-copy', function(e){
			e.preventDefault();
			var text = $(this).closest('.wbw-section').find('.wbw-section-label').text(),
				$temp = $('<textarea>');
			$temp.val(text).css({ position: 'absolute', left: '-9999px' });
			$('body').append($temp);
			$temp.select();
			document.execCommand('copy');
			$temp.remove();
		});

		_this.fieldsList.sortable({
			cursor: "move",
			axis: "y",
			placeholder: "sortable-placeholder",
		});
		_this.outputsList.sortable({
			cursor: "move",
			axis: "y",
			placeholder: "sortable-placeholder",
		});
		_this.submitsList.sortable({
			cursor: "move",
			axis: "y",
			placeholder: "sortable-placeholder",
		});
		_this.resetsList.sortable({
			cursor: "move",
			axis: "y",
			placeholder: "sortable-placeholder",
		});
		
		_this.content.on('change', 'input.waic-field-tlabel', function() {
			var $section = $(this).closest('.wbw-section');
			$section.find('.wbw-section-tlabel').text($(this).val());
			_this.refreshDynamicLists($section.attr('data-section-label'));
		});
		
		_this.mainButton.on('click', function(e){
			e.preventDefault();
			var cssText = _this.content.find('#waicCssEditor');
			if	(typeof(cssText.get(0).CodeMirrorEditor) !== 'undefined') {
				cssText.val(cssText.get(0).CodeMirrorEditor.getValue());
			}
		
			if (typeof(_this.beforeSaveAddon) == 'function') _this.beforeSaveAddon();
			if (typeof(_this.beforeSavePro) == 'function') _this.beforeSavePro();
			
			var $btn = $(this);
			$.sendFormWaic({
				elem: $btn,
				data: {
					mod: 'forms',
					action: 'saveForm',
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
		
	}
	FormsAdminPage.prototype.showLogDialog = function() {
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
					_this.logDialogWrapper.find('#waicLogRequest').html($row.find('.waic-log-question').attr('data-value'));
					_this.logDialogWrapper.find('#waicLogResponse').html($row.find('.waic-log-answer').attr('data-value'));
					_this.logDialogWrapper.parent().find('.ui-dialog-buttonset button').removeClass('ui-button ui-corner-all ui-widget');
				}
			});
		}
		_this.logDialog.dialog('open');
	}
	
	FormsAdminPage.prototype.initHistoryTable = function () {
		var _this = this.$obj,
			url = typeof(ajaxurl) == 'undefined' || typeof(ajaxurl) !== 'string' ? WAIC_DATA.ajaxurl : ajaxurl,
			strPerPage = ' ' + waicCheckSettings(_this.langSettings, 'lengthMenu');
		$.fn.dataTable.ext.classes.sPageButton = 'button button-small wbw-paginate';
		
		_this.historyTableObj = _this.historyTable.DataTable({
			serverSide: true,
			processing: true,
			ajax: {
				'url': url + '?mod=forms&action=getHistoryPage&pl=waic&reqType=ajax&waicNonce=' + WAIC_DATA.waicNonce,
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
					targets: [0,4,7]
				},
				{
					className: "dt-left",
					targets: [2,3,5,6]
				},
				{
					className: "dt-right",
					targets: [4]
				},
				{
					"orderable": false,
					targets: [5,6,7]
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
			_this.currentLogRow = $(this).closest('tr').clone();
			_this.showLogDialog();
			$(this).blur();
			return false;
		});
	}

	FormsAdminPage.prototype.showEditNameDialog = function () {
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

	app.waicFormsAdminPage = new FormsAdminPage();

	$(document).ready(function () {
		app.waicFormsAdminPage.init();
	});

}(window.jQuery, window));
