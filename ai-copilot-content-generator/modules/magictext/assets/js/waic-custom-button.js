(function() {
	tinymce.PluginManager.add('my_custom_button', function(editor, url) {
		if (editor.ui && editor.ui.registry && editor.ui.registry.addMenuButton) {
			const newMenuItems = [];

			for (const [key, item] of Object.entries(WaicMagicTextData.items)) {
				newMenuItems.push({
					type: 'menuitem',
					text: item.name,
					icon: false,
					classes: 'waic-icon-' + key,
					onAction: function () {
						waicMTMCEClickItem(key);
					}
				});
			}

			editor.ui.registry.addMenuButton('my_custom_button', {
				text: 'Magic',
				icon: false,
				fetch: function(callback) {
					callback(newMenuItems);
				}
			});
		}

		else if (editor.addButton) {
			const oldMenuItems = [];
			for (const [key, item] of Object.entries(WaicMagicTextData.items)) {
				oldMenuItems.push({
					text: item.name,
					icon: false,
					onclick: function () {
						waicMTMCEClickItem(key);
					},
					classes: 'waic-icon-' + key
				});
			}

			editor.addButton('my_custom_button', {
				type: 'menubutton',
				text: WaicMagicTextData.lang.ask_ai,
				icon: false,
				menu: oldMenuItems,
				onPostRender: function() {
					const btn = this.getEl().querySelector('button');
					if (btn) {
						btn.innerHTML = '<img src="' + WaicMagicTextData.icon_url + '" style="vertical-align:middle;margin-right:4px;" /> ' + WaicMagicTextData.lang.ask_ai;
					}
				}
			});
		}
	});
})();

window.waicMTMCETranslateCustom = {
	doTranslate: function(params) {
		const lang = jQuery('select[name="language"]').val();
		waicMTMCESendRequest(params.item, params.selectedText, lang);
	},
	doCustom: function (params) {
		const prompt = jQuery('textarea[name="custom_prompt"]').val();
		waicMTMCESendRequest(params.item, params.selectedText, false, prompt);
	}
};

function waicMTMCEClickItem(item) {
	const selectedText = waicMTMCEGetSelectedText();

	if ('translate' === item) {
		waicShowConfirm('<div><span>' + WaicMagicTextData.lang.translate_to + ': </span>' + waicMTMCEGetLangSelect() + '</div>', 'waicMTMCETranslateCustom', 'doTranslate', {
		 	item: item,
			selectedText: selectedText,
		});
	} else if ('cust' === item) {
		waicShowConfirm('<div><span>' + WaicMagicTextData.lang.custom_prompt + ': </span>' + waicMTMCEGetCustomTextarea(), 'waicMTMCETranslateCustom', 'doCustom', {
			item: item,
			selectedText: selectedText,
		});
	} else {
		waicMTMCESendRequest(item, selectedText);
	}
}

function waicMTMCEGetSelectedText() {
	return tinymce.activeEditor.selection.getContent({ format: 'text' });
}

function waicMTMCEGetLangSelect()
{
	jQuery('select[name="language"]').first().remove();

	let s = '<select name="language" class="waicMagicTextLangSelect">';
	for (const [key, item] of Object.entries(WaicMagicTextData.language)) {
		s += '<option value="' + item + '">' + item + '</option>';
	}

	s += '</select>';
	return s;
}

function waicMTMCEGetCustomTextarea() {
	jQuery('textarea[name="custom_prompt"]').first().remove();

	return '<div><textarea name="custom_prompt" class="wbw-fullwidth" rows="4"></textarea></div>'
}

function waicMTMCESendRequest(item, selectedText, lang, prompt) {
	waicShowMTDialog();

	let params = {
		item: item,
		selected: selectedText,
	};

	if (typeof lang !== 'undefined' && lang) {
		params.lang = lang;
	}

	if (typeof prompt !== 'undefined') {
		params.prompt = prompt;
	}

	jQuery.post(WAIC_DATA.ajaxurl, {
		pl: 'waic',
		reqType: 'ajax',
		waicNonce: WAIC_DATA.waicNonce,
		mod: 'magictext',
		action: 'getText',
		params: params,
	}, function (data){
		waicHideMTDialog();
		let parsed;
		try {
			parsed = (typeof data === 'string') ? JSON.parse(data) : data;
		} catch (e) {
			return;
		}

		if (parsed.error) {
			wp.data.dispatch('core/notices').createErrorNotice(
				parsed.errors[0],
				{
					isDismissible: true,
				}
			);
		} else {
			const replacementText = parsed.messages[0];
			tinymce.activeEditor.selection.setContent(replacementText);
		}
	});
}
