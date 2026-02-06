(function ($, app) {
"use strict";
	var PostsCreatePage = app.waicPostsCreatePage;
	
	PostsCreatePage.constructor.prototype.initAddons = function () {
		var _this = this.$obj;

		_this.postsListTable = _this.content.find('#waicPostsList');
		_this.postsListTableWrapper = _this.postsListTable.closest('.waic-table-data');
		_this.postsSelectTable = _this.content.find('#waicPostsSelect');
		_this.postsSelectTableWrapper = _this.postsSelectTable.closest('.waic-table-list');
		_this.initPostsListTable();
	
		_this.content.find('#waicAddPosts').on('click', function(e){
			e.preventDefault();
			_this.activeBodySection = $(this);
			_this.showPostsDialog();
			$(this).blur();
			//return false;
		});
		_this.content.find('#waicDeletePosts').on('click', function(e){
			e.preventDefault();
			var removed = false;
			_this.postsListTableWrapper.find('.waicCheckOne:checked').each(function() {
				_this.postsTableObj.row($(this).parents('tr')).remove().draw();
				removed = true;
			});
			if (removed) _this.postsTableObj.draw();
			$(this).blur();
			return false;
		});
		_this.selectTableRefreshing = false;
		//_this.postsTableRefreshing = false;
	
		$(window).on('resize', function() {
			setTimeout(function () {
				_this.postsTableObj.draw();
				if (_this.postsSelectTableObj && !_this.selectTableRefreshing) _this.postsSelectTableObj.draw();
			}, 100);
		});
	
	}
	PostsCreatePage.constructor.prototype.initPostsListTable = function () {
		var _this = this.$obj,
			strPerPage = ' ' + waicCheckSettings(_this.langSettings, 'lengthMenu');
	
		//$.fn.dataTable.ext.classes.sPageButton = 'button button-small wbw-paginate';
		_this.postsTableObj = _this.postsListTable.DataTable({
			lengthChange: true,
			lengthMenu: [ [10, 100, -1], [10 + strPerPage, 100 + strPerPage, waicCheckSettings(_this.langSettings, 'pageAll')] ],
			paging: true,
			//dom: '<"pull-right"l>rtip',
			dom: 't<"waic-table-pages"pl>',
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
				},
				{
					"orderable": false,
					targets: [0,1,2,3]
				}
			],
			orderable: false,
			order: false,
			scrollCollapse: true,
			scrollY: '260px',
			scrollX: false,
			language: {
				emptyTable: waicCheckSettings(_this.langSettings, 'emptyTable'),
				loadingRecords: '*',
				paginate: {
					next: waicCheckSettings(_this.langSettings, 'pageNext'),
					previous: waicCheckSettings(_this.langSettings, 'pagePrev'),
					last: '<i class="fa fa-fw fa-angle-right">',
					first: '<i class="fa fa-fw fa-angle-left">'
				},
				lengthMenu: ' _MENU_',
			},
			fnDrawCallback : function() {
				setTimeout(function () {
					$('#waicPostsList_wrapper .dt-paging')[0].style.display = $('#waicPostsList_wrapper .dt-paging button').length > 5 ? 'block' : 'none';
				}, 50);
				_this.postsListTableWrapper.find('.waicCheckAll').prop('checked', false);
				waicInitCheckAll(_this.postsListTableWrapper);
			}
		});
	}
		
	PostsCreatePage.constructor.prototype.showPostsDialog = function () {
		var _this = this.$obj;
		
		if (!_this.postsListDialog) {
			_this.postsListDialog = $('#waicPostsListDialog').removeClass('wbw-hidden').dialog({
				modal: true,
				autoOpen: false,
				position: {my: 'center', at: 'center', of: window},
				width: '90%',
				minHeight: 550,
				dialogClass: "wbw-plugin",
				buttons: [
					{
						text: waicCheckSettings(_this.langSettings, 'add-btn'),
						class: 'wbw-button wbw-button-form wbw-button-main',
						click: function() {
							jQuery(this).dialog('close');
							_this.content.find('#waicAddPosts').blur();
							var list = {};
							_this.postsSelectTableWrapper.find('.waicCheckOne:checked').each(function() {
								var $this = $(this);
								list[$this.attr('data-id')] = $this.closest('tr').find('.waic-post-link').text();
							});
							//console.log(list);
							//var value = _this.postsListDialog.find('#waicTopicList').val();
							_this.addPostsListRows(list);
						},
					},
					{
						text: waicCheckSettings(_this.langSettings, 'cancel-btn'),
						class: 'wbw-button wbw-button-form wbw-button-minor',
						click: function() {
							jQuery(this).dialog('close');
							_this.content.find('#waicAddPosts').blur();
						}
					}
				],
				open: function() {
					//_this.postsListDialog.find('#waicTopicList').val('');
					if (_this.postsSelectTableObj) _this.postsSelectTableObj.draw();
					else _this.initPostsSelectTable();
					jQuery(this).parent().find('.ui-dialog-buttonset button').removeClass('ui-button ui-corner-all ui-widget');
				}
			});
			$('#waicPostsListDialog #waicFilterPosts').on('click', function() {
				if (_this.postsSelectTableObj) _this.postsSelectTableObj.draw();
				$(this).blur();
			});
			
		}
		_this.postsListDialog.dialog('open');
	}
	PostsCreatePage.constructor.prototype.initPostsSelectTable = function () {
		var _this = this.$obj,
			strPerPage = ' ' + waicCheckSettings(_this.langSettings, 'lengthMenu'),
			url = typeof(ajaxurl) == 'undefined' || typeof(ajaxurl) !== 'string' ? WAIC_DATA.ajaxurl : ajaxurl;
	
		//$.fn.dataTable.ext.classes.sPageButton = 'button button-small wbw-paginate';
		_this.postsSelectTableObj = _this.postsSelectTable.DataTable({
			lengthChange: true,
			lengthMenu: [ [10, 100, -1], [10 + strPerPage, 100 + strPerPage, waicCheckSettings(_this.langSettings, 'pageAll')] ],
			paging: true,
			dom: 't<"waic-table-pages"pl>',
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
				},
				{
					"orderable": false,
					targets: [0,1,2,3]
				}
			],
			orderable: false,
			order: false,
			scrollCollapse: true,
			scrollY: '260px',
			scrollX: false,
			language: {
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
			serverSide: true,
			processing: true,
			deferLoading: 1,
			ajax: {
				'url': url + '?mod=postsfields&action=searchPostsList&pl=waic&reqType=ajax&waicNonce=' + WAIC_DATA.waicNonce,
				"type": "POST",
				data: function (d) {
					var filters = $('#waicSearchPostsFilters');
					d.filter_title = filters.find('input[name="post_title"]').val();
					d.filter_categories = filters.find('select[name="post_categories[]"]').val();
					d.filter_tags = filters.find('select[name="post_tags[]"]').val();
				},
				beforeSend: function() {
					_this.selectTableRefreshing = true;
					//$('#wtbpCreateTable').DataTable().column(4).visible($('#wtbpCreateTable_wrapper .dt-buttons input[name="show_variations"]').is(':checked'));
				}
			},
			preDrawCallback: function (settings, json) {
				$('#waicPostsSelect_wrapper .dt-processing').css('top', '35px');
				$('#waicPostsSelect_wrapper .dt-processing > div:not(.waic-loader)').css('display', 'none');
			},
			fnDrawCallback : function() {
				setTimeout(function () {
					$('#waicPostsSelect_wrapper .dt-paging')[0].style.display = $('#waicPostsSelect_wrapper .dt-paging button').length > 5 ? 'block' : 'none';
				}, 50);
				_this.postsSelectTableWrapper.find('.waicCheckAll').prop('checked', false);
				waicInitCheckAll(_this.postsSelectTableWrapper);
				_this.selectTableRefreshing = false;
			}
		});
	}
	
	PostsCreatePage.constructor.prototype.addPostsListRows = function ( posts ) {
		var _this = this.$obj,
			nodes = _this.postsTableObj.rows().nodes(0),
			cnt = nodes.length,
			ids = [],
			rows = [];
		for (var i = 0; i < cnt; i++) {
			ids.push($(nodes[i]).find('input.waicCheckOne').attr('data-id'));
		}
			
		for (var id in posts) {
			if (ids.indexOf(id) == -1) {
				rows.push(['<input type="checkbox" class="waicCheckOne" data-id="' + id + '">', posts[id], '<input type="text" class="waic-field-topic">', '<input type="text" class="waic-field-keywords">']);
			}
		}
		if (rows.length) {
			_this.postsTableObj.rows.add(rows).draw();
		}
	}
	PostsCreatePage.constructor.prototype.beforeSaveAddon = function () {
		var _this = this.$obj,
			posts = [],
			rows = _this.postsTableObj.rows().nodes(0),
			cnt = rows.length,
			ids = [];
		for (var i = 0; i < cnt; i++) {
			var $row = $(rows[i]);
			posts.push([$row.find('input.waicCheckOne').attr('data-id'), $row.find('input.waic-field-topic').val(), $row.find('input.waic-field-keywords').val()]);
		}
		
		_this.content.find('#waicPostsTopics').val(JSON.stringify(posts));
	}

	
}(window.jQuery, window));
