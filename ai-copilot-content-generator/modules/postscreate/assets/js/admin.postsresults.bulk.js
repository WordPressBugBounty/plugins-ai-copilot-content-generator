(function ($, app) {
"use strict";
	var PostsResultsPage = app.waicPostsResultsPage;

	PostsResultsPage.constructor.prototype.initBulk = function () {
		var _this = this.$obj;
		_this.tableResultsBulk = false;
		_this.postResultsDialog = false;
		_this.currentPost = false;
		_this.scrollBlock = false;
		_this.publishButton = _this.content.find('#waicTaskPublish');
		_this.deleteButton = _this.content.find('#waicTaskDelete');
		_this.groupButtons = _this.content.find('.waic-group-button');
		
		if (_this.isBulkMode) {
			_this.postWrapper = false;
			_this.tableResultsBulk = _this.tableResults.find('#waicPostsResultsBulk');
			_this.initTableResultsBulk();
		}

		_this.eventsPostsResultsPageBulk();
		
	}
	
	PostsResultsPage.constructor.prototype.eventsPostsResultsPageBulk = function () {
		var _this = this.$obj;
		if (_this.tableResultsBulk) {
			$(window).on('resize', function(event) {
				_this.tableResultsBulkObj.columns.adjust();
				setTimeout(function() {
					_this.tableResultsBulkObj.columns.adjust();
				}, 100);
				_this.postResultsInitBulk(true);
			});
		
			_this.tableResultsBulk.on('click', '.waic-result-preview', function() {
				//var id = $(this).closest('tr').find(' 
				_this.currentPost = $(this).attr('data-post');
				_this.scrollBlock = $(this).attr('data-block');
				_this.showPostResultsDialog();
			});
		}

		_this.tableResults.on('change', '.waicCheckAll, .waicCheckOne', function(e) {
			_this.groupButtons.prop('disabled', !_this.taskActions.running && _this.tableResultsBulk.find('.waicCheckOne:checked').length ? '' : 'disabled');
		});
		
		_this.publishButton.on('click', function(e){
			e.preventDefault();
			_this.setActionPostIds();
			if (_this.actionPostIds) {
				_this.activeButton = $(this);
				_this.doActionTask(0);
			}
			return false;
		});
		_this.deleteButton.click(function(e){
			e.preventDefault();
			_this.setActionPostIds();
			if (_this.actionPostIds) {
				_this.activeButton = $(this);
				waicShowConfirm(waicCheckSettings(_this.langSettings, 'confirm-delete'), 'waicPostsResultsPage', 'doActionTask', 0);
			}
			return false;
		});
		_this.tableResults.on('click', '.waic-get-social', function(e) {
			e.preventDefault();
			
			var $btn = $(this);
				
			$.sendFormWaic({
				elem: $btn,
				data: {
					mod: 'postsrss',
					action: 'getSocialPost',
					post_id: $btn.attr('data-post'),
					social: $btn.attr('data-social'),
					task_id: _this.taskId,
				},
				onSuccess: function(res) {
					if (!res.error && res.data) {
						waicShowConfirm('<div class="waic-social-text">' + res.data + '</div>', 'waicPostsResultsPage', 'copyHtml', res.data, {'ok': 'Copy'});
					}
				}
			});
			return false;
		});
		_this.content.find('#waicScheduleActive').on('change', function(e) {
			e.preventDefault();
			
			var $btn = $(this),
				isOn = $btn.is(':checked') ? 1 : 0;
				
			$.sendFormWaic({
				elem: $btn,
				data: {
					mod: 'postsrss',
					action: 'activedSchedule',
					is_on: isOn,
					task_id: _this.taskId,
				},
				onSuccess: function(res) {
					if (!res.error) {
						location.reload();
					}
				}
			});
			location.reload();
		});
	}
	PostsResultsPage.constructor.prototype.copyHtml = function ($txt) {
		if (navigator.clipboard && window.isSecureContext) {
			navigator.clipboard.writeText($txt);
		} else {
			const textArea = document.createElement('textarea');
			textArea.value = $txt;
			textArea.style.position = "absolute";
			textArea.style.left = "-999999px";
				
			document.body.prepend(textArea);
			textArea.select();

			try {
				document.execCommand('copy');
			} catch (error) {
				console.error(error);
			} finally {
				textArea.remove();
			}
		}
	}
	PostsResultsPage.constructor.prototype.setActionPostIds = function () {
		var _this = this.$obj,
			ids = [];
		_this.tableResultsBulk.find('.waicCheckOne:checked').each(function () {
			ids.push($(this).attr('data-post'));
		});
		_this.actionPostIds = ids.length ? ids : false;
	}
	PostsResultsPage.constructor.prototype.initTableResultsBulk = function () {
		var _this = this.$obj,
			url = typeof(ajaxurl) == 'undefined' || typeof(ajaxurl) !== 'string' ? WAIC_DATA.ajaxurl : ajaxurl,
			strPerPage = ' ' + waicCheckSettings(_this.langSettings, 'lengthMenu');
	
		$.fn.dataTable.ext.classes.sPageButton = 'button button-small wbw-paginate';
		url += '?mod=' + _this.tableResultsBulk.attr('data-mod') + '&action=' + _this.tableResultsBulk.attr('data-action') + '&feature=' + _this.tableResultsBulk.attr('data-feature');
		if (_this.tableResultsBulk.attr('data-param')) {
			url += '&param=' + _this.tableResultsBulk.attr('data-param');
		}
		_this.tableResultsBulkObj = _this.tableResultsBulk.DataTable({
			serverSide: true,
			processing: true,
			ajax: {
				'url': url + '&pl=waic&reqType=ajax&waicNonce=' + WAIC_DATA.waicNonce,
				'type': 'POST',
				data: function (d) {
					d.task_id = _this.taskId;
				}
			},
			lengthChange: true,
			lengthMenu: [ [10, 100, -1], [10 + strPerPage, 100 + strPerPage, "All"] ],
			paging: true,
			dom: 'rt<"waic-table-pages"pl>',
			responsive: {details: {display: $.fn.dataTable.Responsive.display.childRowImmediate, type: ''}},
			autoWidth: false,
			columnDefs: [
				{
					width: "20px",
					targets: 0
				},
				{
					className: "dt-left",
					targets: [1,2,3]
				}
			],
			ordering: false,
			order: false,
			scrollCollapse: true,
			scrollY: '340px',
			scrollX: false,
			language: {
				emptyTable: waicCheckSettings(_this.langSettings, 'emptyTable'),
				loadingRecords: '<div class="waic-leer"></div>',
				paginate: {
					next: waicCheckSettings(_this.langSettings, 'pageNext'),
					previous: waicCheckSettings(_this.langSettings, 'pagePrev'),
					last: '<i class="fa fa-fw fa-angle-right">',
					first: '<i class="fa fa-fw fa-angle-left">'
				},
				lengthMenu: ' _MENU_',
				processing: '<div class="waic-loader"><div class="waic-loader-bar bar1"></div><div class="waic-loader-bar bar2"></div></div>',
			},
			preDrawCallback: function (settings, json) {
				$('#waicPostsResultsBulk_wrapper .dt-processing').css('top', '30px');
				$('#waicPostsResultsBulk_wrapper .dt-processing > div:not(.waic-loader)').css('display', 'none');
			},
			fnDrawCallback : function() {
				setTimeout(function () {
					$('#waicPostsResultsBulk_wrapper .dt-paging')[0].style.display = $('#waicPostsResultsBulk_wrapper .dt-paging button').length > 5 ? 'block' : 'none';
				}, 50);
				_this.tableResults.find('.waicCheckAll').prop('checked', false);
				_this.groupButtons.prop('disabled', 'disabled');
				waicInitCheckAll(_this.tableResults);
				if (_this.postResultsDialog && _this.postResultsDialog.dialog('isOpen')) {
					_this.getCurentPostResultsBulk();
				}
				_this.tableResults.find('.waic-post-actions a.waic-post-action').on('click', function(e){
					e.preventDefault();
					_this.activeButton = $(this);
					_this.doActionTask(0);
					return false;
				});

			}
		});
		_this.tableResultsBulkObj.on('xhr', function (e, settings, json, xhr) {
			_this.isRefreshing = false;
			_this.setTaskData(json);
		});
	}
	PostsResultsPage.constructor.prototype.getCurentResultsBulk = function () {
		this.$obj.tableResultsBulkObj.ajax.reload();
	}

	PostsResultsPage.constructor.prototype.showPostResultsDialog = function () {
		var _this = this.$obj;
		
		if (!_this.postResultsDialog) {
			_this.postResultsDialog = $('#waicPostResultsDialog').removeClass('wbw-hidden').dialog({
				modal: true,
				autoOpen: false,
				position: {my: 'center', at: 'top', of: window},
				width: '700px',
				dialogClass: "wbw-plugin",
				buttons: [
					{
						text: waicCheckSettings(_this.langSettings, 'save-btn'),
						class: 'wbw-button wbw-button-form wbw-button-main',
						click: function(e) {
							_this.activeButton = jQuery(e.currentTarget);
							_this.doActionTask(0, 'save');
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
					_this.getCurentPostResultsBulk(true);
				},
				close: function() {
					_this.postWrapper = false;
				}
			});
		}
		_this.postResultsDialog.dialog('open');
	}
	PostsResultsPage.constructor.prototype.getCurentPostResultsBulk = function(isOpen) {
		var _this = this.$obj,
			isOpen = typeof(isOpen) == 'undefined' ? false : isOpen,
			$wrapper = $('#waicPostResultsDialog');
		_this.postWrapper = $wrapper;
		if (isOpen) {
			//$wrapper.html('<button class="waic-btn-loader">'+waicCheckSettings(_this.langSettings, 'waiting-btn')+'</button>');
			$wrapper.html('<div class="waic-loader"><div class="waic-loader-bar bar1"></div><div class="waic-loader-bar bar2"></div></div>');
			$wrapper.parent().find('.ui-dialog-buttonset button').removeClass('ui-button ui-corner-all ui-widget');
		}
		$wrapper.parent().find('.wbw-button-main').prop('disabled', 'disabled');
		//_this.getCurentPostResults($wrapper.find('.waic-btn-loader'), _this.currentPost);
		_this.getCurentPostResults(false, _this.currentPost);
	}
	PostsResultsPage.constructor.prototype.afterActionTaskBulk = function(resize) {
		var _this = this.$obj;
		if (_this.postResultsDialog && _this.postResultsDialog.dialog('isOpen')) {
			_this.postResultsDialog.dialog('close');
		}
	}
	
	PostsResultsPage.constructor.prototype.postResultsInitBulk = function(resize) {
		var _this = this.$obj,
			resize = typeof(resize) == 'undefined' ? false : resize;

		if (_this.postResultsDialog && _this.postResultsDialog.dialog('isOpen')) {
			var $dialog = _this.postResultsDialog.parent(),
				height = $(window).height() - 20;// - $dialog.find('.ui-dialog-buttonset').height() - 20;
			_this.postResultsDialog.dialog('option', 'maxHeight', height);
			$dialog.css({'width': '90%', 'top': '10px', 'left': '5%'});
			//_this.postResultsDialog.css({'height': height + 'px'});
			
			
			if (!resize) {
				var canSave = _this.taskActions.save && _this.postResultsDialog.find('.waic-post-results[data-can-update="1"]').length;
				$dialog.find('.wbw-button-main').prop('disabled', canSave ? '' : 'disabled');
			}
		}
	}

	
}(window.jQuery, window));
