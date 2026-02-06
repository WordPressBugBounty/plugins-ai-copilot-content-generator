(function ($, app) {
"use strict";
	function FormFrontPage() {
		this.$obj = this;
		this.$obj.emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
		return this.$obj;
	}
	
	FormFrontPage.prototype.init = function ($wrapper) {
		var _this = this.$obj,
			$wrapper = typeof($wrapper) == 'undefined' ? $('.aiwu-form-widget-wrapper') : $wrapper;
		if ($wrapper.length == 0) return;
		
		_this.formsList = $wrapper.find('.aiwu-form-widget');
		_this.outputsList = {};
		_this.rulesList = {};
		_this.formsList.each(function() {
			_this.initForm($(this));
		});
		
		if (typeof(_this.initPro) == 'function') _this.initPro();
		if (typeof(_this.initAddons) == 'function') _this.initAddons();

	}

	FormFrontPage.prototype.initForm = function ($wrapper) {
		var _this = this.$obj,
			viewId = $wrapper.attr('data-viewid'),
			taskId = $wrapper.attr('data-task-id'),
			$customCss = $wrapper.find('style');
		if ($customCss.length) {
			var $styleWrapper = $('style#waicFormsCustomCss');
			if ($styleWrapper.length == 0) {
				$styleWrapper = $('<style type="text/css" id="waicFormsCustomCss">');// + $customCss + '</style>);
				$styleWrapper.appendTo('head');
			}
			$styleWrapper.append($customCss.html());
		}
		_this.outputsList[viewId] = {};
		_this.rulesList[viewId] = waicParseJSON($wrapper.find('input[name="form_rules"]').val());
		
		$wrapper.find('.aiwu-field-wrapper[data-hide="1"]').addClass('aiwu-hidden');
		
		$wrapper.find('.aiwu-form-output').each(function() {
			var $output = $(this),
				settings = waicParseJSON($output.attr('data-settings')),
				selector = waicCheckSettings(settings, 'selector', false),
				isCustom = selector && selector.length,
				$realOutput = isCustom ? jQuery(selector).first() : $output.find('.aiwu-output-text');
			if ($realOutput.length == 0) {
				$realOutput = $output.find('.aiwu-output-text');
				isCustom = false;
			}
			_this.outputsList[viewId][$output.attr('data-id')] = $realOutput;
			if (isCustom) {
				$output.addClass('aiwu-hide');
			}
			$output.attr('data-custom', isCustom ? 1 : 0);
			if (waicCheckSettings(settings, 'hide', false)) {
				if (isCustom) $realOutput.addClass('aiwu-hidden');
				else $output.addClass('aiwu-hidden');
			}
			var initial = waicCheckSettings(settings, 'initial', false);
			if (initial && initial.length) $realOutput.html(initial);
		});
		
		$wrapper.find('.aiwu-button-reset').on('click', function() {
			var $reset = $(this),
				settings = waicParseJSON($reset.attr('data-settings')),
				fields = waicCheckSettings(settings, 'fields', ''),
				outputs = waicCheckSettings(settings, 'outputs', '');
			if (fields.length) {
				var isAll = fields == 'all',
					list = isAll ? [] : fields.split(',');
				$wrapper.find('.aiwu-form-field').each(function() {
					var $elem = $(this);
					if (isAll || list.indexOf($elem.attr('data-id').replace('[]','')) != -1) {
						_this.setFieldValue($elem, '');
					}
				});
			}
			if (outputs.length) {
				var isAll = outputs == 'all',
					list = isAll ? [] : outputs.split(',');
				$wrapper.find('.aiwu-form-output').each(function() {
					var $output = $(this),
						id = $output.attr('data-id');
					if (isAll || list.indexOf(id) != -1) {
						var settings = waicParseJSON($output.attr('data-settings')),
							$realOutput = _this.outputsList[viewId][id],
							isCustom = $output.attr('data-custom') == 1,
							initial = waicCheckSettings(settings, 'initial', '');
						if (waicCheckSettings(settings, 'hide', false)) {
							if (isCustom) $realOutput.addClass('aiwu-hidden');
							else $output.addClass('aiwu-hidden');
						}
						$realOutput.html(initial);
					}
				});
			}
		});
		$wrapper.find('.aiwu-button-submit').on('click', function() {
			var $submit = $(this),
				settings = waicParseJSON($submit.attr('data-settings')),
				fields = waicCheckSettings(settings, 'fields', ''),
				selectors = waicCheckSettings(settings, 'selectors', ''),
				data = {},
				error = false;
			if (fields.length) {
				for (var i = 0; i < fields.length; i++) {
					var parts = fields[i].split('_');
					if (parts.length == 2) {
						var $field = _this.getFormField($wrapper, parts[1], parts[0] == 'OUPUT' ? '.aiwu-form-output' : '.aiwu-form-field');
						if ($field.length == 1) {
							var f = $field[0];
							if (!f.checkValidity()) {
								f.reportValidity();
								error = true;
								break;
							} 
						}
						var value = _this.getFormFieldValue($field).join(',');
						if ($field.length > 1) {
							var $elem = $field.closest('.aiwu-field-elem');
							if ($elem && $elem.attr('data-required') == 1) {
								var f = $field[0];
								if (value.length == 0) {
									f.required = true;
									if (!f.checkValidity()) {
										f.reportValidity();
										error = true;
										break;
									}
								} else f.required = false;
							}
						}
						data[fields[i]] = value;
					}
				}
			}
			if (error) return;
			if (selectors.length) {
				for (var i = 0; i < selectors.length; i++) {
					var $elem = $(selectors[i]);
					if ($elem.length) {
						data[selectors[i]] = $elem.text();
					}
				}
			}
			var output = waicCheckSettings(settings, 'output', ''),
				needScroll = waicCheckSettings(settings, 'scroll', 0);
			if (output != 'custom') {
				var $output = _this.setLoader($wrapper, output);
				if ($output.length && needScroll) _this.scrollToOutput(_this.getRealOutput($output, $wrapper));
			} else {
				if (needScroll) _this.scrollToOutput($(waicCheckSettings(settings, 'selector', '')));
			}
			
			$.sendFormWaic({
				data: {
					mod: 'forms',
					action: 'sendForm',
					task_id: taskId,
					submit: $submit.attr('data-id'),
					fields: data,
				},
				onSuccess: function(res) {
					if (!res.error && res.data && res.data.result) {
						_this.setResult($wrapper, res.data.result);
					}
					_this.doRules($wrapper);
				}
			});
		});
		_this.doRules($wrapper);
		$wrapper.find('.aiwu-form-field').on('change', function() {
			_this.doRules($wrapper);
		});
		$wrapper.closest('.aiwu-form-widget-wrapper').removeAttr('style');
	}
	FormFrontPage.prototype.scrollToOutput = function ($output) {
		if ($output.length) {
			$output[0].scrollIntoView({ behavior: 'smooth', block: 'end' });
		}
	}
	FormFrontPage.prototype.setLoader = function ($wrapper, output) {
		var _this = this.$obj,
			$output = _this.getFormField($wrapper, output, '.aiwu-form-output');
		if ($output) {
			var settings = waicParseJSON($output.attr('data-settings')),
				replace = waicCheckSettings(settings, 'replace') == 1,
				loader = waicCheckSettings(settings, 'loader', '');
			if (loader && loader.length) {
				_this.setFieldValue($output, loader, replace ? false : $('<div>').addClass('aiwu-output-loader'));
			}
			
			if (waicCheckSettings(settings, 'hide', false)) {
				$output.removeClass('aiwu-hidden');
				_this.getRealOutput($output, $wrapper).removeClass('aiwu-hidden');
			}
		}
		
		return $output;
	}
	FormFrontPage.prototype.setResult = function ($wrapper, result) {
		var _this = this.$obj,
			submit = result.submit,
			$submit = _this.getFormField($wrapper, submit, '.aiwu-button-submit');
		if ($submit) {
			var settings = waicParseJSON($submit.attr('data-settings')),
				output = waicCheckSettings(settings, 'output');
			if (output == 'custom') {
				var selector = waicCheckSettings(settings, 'selector');
				if (selector.length) $(selector).html(result.answer);
			} else {
				var $output = _this.getFormField($wrapper, output, '.aiwu-form-output');
				if ($output) {
					var settings = waicParseJSON($output.attr('data-settings')),
						replace = waicCheckSettings(settings, 'replace') == 1;
					if (result.error) {
						var error = waicCheckSettings(settings, 'error');
						if (!error || error.length == 0) error = result.answer;
						_this.setFieldValue($output, error, replace ? false : $('<div>').addClass('aiwu-output-error'));
					} else {
						_this.setFieldValue($output, result.answer, replace ? false : $('<div>').addClass('aiwu-output-result'));
					}
				}
			}
		}
	}

	FormFrontPage.prototype.doRules = function ($wrapper) {
		var _this = this.$obj,
			viewId = $wrapper.attr('data-viewid');

		if (!(viewId in _this.rulesList) || _this.rulesList[viewId].length == 0) return;
		for (var key in _this.rulesList[viewId]) {
			var rule = _this.rulesList[viewId][key];
			if (!('ifs' in rule) || !('thens' in rule) || rule.ifs.length == 0 || rule.thens.length == 0) continue;
			
			var isAnd = 'logic' in rule && rule.logic == 'and',
				result = isAnd;
			for (var i = 0; i < rule.ifs.length; i++) {
				var ifRule = rule.ifs[i],
					$field = _this.getFormField($wrapper, ifRule.target),
					values = _this.getFormFieldValue($field),
					value = ifRule.value,
					res = false;
				if ($field.length) {
					switch (ifRule.operator) {
						case 'equals':
							if (values.indexOf(value) != -1) res = true;
							break;
						case 'not_equals':
							if (values.indexOf(value) == -1) res = true;
							break;
						case 'contains':
							for (var v = 0; v < values.length; v++) {
								if (values[v].includes(value)) {
									res = true;
									break;
								}
							}
							break;
						case 'not_contains':
							var found = false;
							for (var v = 0; v < values.length; v++) {
								if (values[v].includes(value)) {
									found = true;
									break;
								}
							}
							if (!found) res = true;
							break;
						case 'greater':
							var num = Number(value),
								isNum = !isNaN(num);
							for (var v = 0; v < values.length; v++) {
								if ((isNum && num<Number(values[v])) || (!isNum && value>values[v])) {
									res = true;
									break;
								}
							}
							break;
						case 'less':
							var num = Number(value),
								isNum = !isNaN(num);
							for (var v = 0; v < values.length; v++) {
								if ((isNum && num>Number(values[v])) || (!isNum && value<values[v])) {
									res = true;
									break;
								}
							}
							break;
						case 'empty':
							if (values.length == 0) res = true;
							else {
								var found = false;
								for (var v = 0; v < values.length; v++) {
									if (values[v].length > 0) {
										found = true;
										break;
									}
								}
							}
							if (!found) res = true;
							break;
						case 'not_empty':
							for (var v = 0; v < values.length; v++) {
								if (values[v].length) {
									res = true;
									break;
								}
							}
							break;
					}
				}
				if (res && !isAnd) {
					result = true;
					break;
				}
				if (!res && isAnd) {
					result = false;
					break;
				}
			}
			var $actions = result ? rule.thens : rule.elses;
			if (typeof $actions != 'undefined' && $actions.length) {
				for (var i = 0; i < $actions.length; i++) {
					var actionRule = $actions[i],
						$field = _this.getFormField($wrapper, actionRule.target),
						value = actionRule.value;
					if ($field.length) {
						switch (actionRule.action) {
							case 'show':
								_this.showHideFormField($field.first(), true);
								break;
							case 'hide':
								_this.showHideFormField($field.first(), false);
								break;
							case 'enable':
								_this.disableFormField($field, true);
								break;
							case 'disable':
								_this.disableFormField($field, false);
								break;
							case 'value':
								_this.setFieldValue($field, actionRule.value);
								break;
						}
					}
				}
			}
		}
	}
	FormFrontPage.prototype.disableFormField = function ($field, enable) {
		if ($field.is('input, select, textarea')) {
			if (enable) $field.removeAttr('disabled');
			else $field.attr('disabled', 'disabled').prop('disabled', true);
		}
	}
	FormFrontPage.prototype.getRealOutput = function ($field, $wrapper) {
		if ($field.hasClass('aiwu-form-output')) {
			var _this = this.$obj,
				$wrapper = typeof($wrapper) == 'undefined' ? $field.closest('.aiwu-form-widget') : $wrapper,
				viewId = $wrapper.length ? $wrapper.attr('data-viewid') : '';
			if (viewId in _this.outputsList) return _this.outputsList[viewId][$field.attr('data-id')];
		}
		return false;
	}
	FormFrontPage.prototype.showHideFormField = function ($field, show) {
		var $wrapper = $field.closest('.aiwu-field-wrapper');
		if ($wrapper.length == 0) {
			if ($field.hasClass('aiwu-form-output') && $field.attr('data-custom') == 1) {
				$wrapper = this.$obj.getRealOutput($field);
			}
		}
		if ($wrapper && $wrapper.length) {
			if (show) $wrapper.removeClass('aiwu-hidden');
			else $wrapper.addClass('aiwu-hidden');
		}
	}
	FormFrontPage.prototype.getFormField = function ($wrapper, id, selector) {
		var $fields = $wrapper.find((typeof(selector) == 'string' ? selector : '')+'[data-id="'+id+'"]');
		if ($fields.length == 0 && selector) {
			$fields = $wrapper.find(selector+' [data-id="'+id+'"]');
		}
		return $fields;
		/*var var _this = this.$obj,
			$field = $wrapper.find('[data-id="'+id+'"');
		if ($field.length == 1 && $field.hasClass('waic-form-output')) {
			$field = _this.outputsList[$wrapper.attr('data-view-id')][id];
		}
		return $field;*/
	}
	FormFrontPage.prototype.getFormFieldValue = function ($field) {
		var values = [];
		if ($field && $field.length) {
			if ($field.is('input[type="radio"], input[type="checkbox"]')) {
				$field.filter(':checked').each(function() {
					values.push($(this).attr('value'));
				});
			} else if ($field.is('div')) {
				if ($field.hasClass('aiwu-form-output')) {
					$field = this.$obj.getRealOutput($field);
					if ($field) values.push($field.html());
				}
				values.push($field.html());
			}
			else if ($field.is('input, select, textarea')) values.push($field.val());
		} 
		return values;
	}
	FormFrontPage.prototype.setFieldValue = function ($field, value, $add) {
		if ($field.is('select')) {
			if ($field.find('option[value="'+value+'"]').length == 0) value = $field.find('option').first().attr('value');
			$field.val(value);
		} else if ($field.is('input[type="radio"], input[type="checkbox"]')) {
			$field.filter('[value="'+value+'"]').prop('checked', true);
		} else if ($field.is('div')) {
			$field = this.$obj.getRealOutput($field);
			if ($field) {
				if ($add) {
					if ($field.find('div').length == 0) $field.html('');
					$field.find('.aiwu-output-loader').remove();
					$field.append($add.html(value));
				}
				else $field.html(value);
			}
		} else $field.val(value);
		return $field;
	}
	
	app.aiwuForms = new FormFrontPage();

	$(document).ready(function () {
		app.aiwuForms.init();
	});

}(window.jQuery, window));
