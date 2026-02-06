import React, { useState, useEffect } from 'react';
import HtmlEditorModal from './html.editor.modal.jsx';

function useDebouncedValue(value, delay = 300) {
	const [debouncedValue, setDebouncedValue] = useState(value);

	useEffect(() => {
		const handler = setTimeout(() => {
			setDebouncedValue(value);
		}, delay);

		return () => {
			clearTimeout(handler);
		};
	}, [value, delay]);

	return debouncedValue;
}

export default function SidebarPanel({ node, onClose, onOpenVariables, onCursorCapture, exposeHandle }) {
	if (!node) return null;
	const [step, setStep] = useState('category'); // 'category' | 'list' | 'settings'
	const [selectedCategoryId, setSelectedCategoryId] = useState(null);
	const [selectedItem, setSelectedItem] = useState(null);
	const [searchQuery, setSearchQuery] = useState('');
	const debouncedSearch = useDebouncedValue(searchQuery, 300);
	const [htmlEditorOpen, setHtmlEditorOpen] = useState(false);
	const [htmlEditorKey, setHtmlEditorKey] = useState(null);
	const [htmlEditorContent, setHtmlEditorContent] = useState('');

	const nodeType = node?.type;

	const config = window.WAIC_WORKFLOW?.blocks?.[nodeType+'s'] || {};
	const lang = window.WAIC_WORKFLOW?.lang || {};

	const categories = Object.entries(config).map(([key, value]) => ({
		id: key || '',
		name: value.name || '',
		desc: value.desc || '',
		items: value.list || [],
	}));
	const handleSettingChange = (key, value) => {
		if (!node || !node.data || !selectedItem) return;
		const currentValues = {
			...node.data.settings,
			[key]: value,
		};
		const errorKey = node.data.error || false;
		const filteredSettings = getVisibleSettings(selectedItem.settings, currentValues);

		onClose({
			...node,
			data: {
				...node.data,
				settings: filteredSettings,
				error: errorKey === key ? false : errorKey,
			},
		});
	};

	useEffect(() => {
		setSelectedItem(null);
		setSelectedCategoryId(null);
		setStep('category');
		setSearchQuery('');
	
		const { category, code } = node.data || {};
		const safeCategory = category ?? '';
		const categoryConfig = config[safeCategory] || config[''];
		setSearchQuery('');

		if (code && categoryConfig?.list) {
			const found = categoryConfig.list.find((item) => item.code === code);
			if (found) {
				setSelectedCategoryId(safeCategory);
				setSelectedItem(found);
				setStep('settings');
				return;
			}
		}
		setStep('category');
		setSelectedCategoryId(null);
		setSelectedItem(null);
	}, [node?.id]);
	useEffect(() => {
		if (exposeHandle) {
			exposeHandle(handleSettingChange);
		}
		waicInitTooltips('.waic-sidebar-body');
	}, [node]);
	useEffect(() => {
		if (step === 'category') setSelectedCategoryId(null);
	}, [step]);

	const handleCategorySelect = (catId) => {
		setSelectedCategoryId(catId);
		setStep('list');
		onClose({
			...node,
			data: {
				...node.data,
				type: nodeType,
				category: catId,
				error: false,
			},
		});
	};
	const handleItemSelect = (item) => {
		setSelectedItem(item);
		setStep('settings');
		onClose({
		...node,
			data: {
				...node.data,
				type: nodeType,
				category: selectedCategoryId ?? 'un',
				code: item.code,
				label: item.name,
				settings: getVisibleSettings(item.settings, {}),
				error: false,
			},
		});
	};
	const getVisibleSettings = (settingsConfig, values) => {
		const result = {};
		const effectiveValues = getEffectiveSettingsValues(settingsConfig, values);

		Object.entries(settingsConfig).forEach(([key, setting]) => {
			if (setting.inner) return;
			if (!shouldShowSetting(setting, effectiveValues)) return;

			//result[key] = effectiveValues[key];
			if (key in settingsConfig) {
				result[key] = effectiveValues[key];
			}
			if (setting.add) {
				setting.add.forEach((addKey) => {
					const addSetting = settingsConfig[addKey];
					if (addSetting && shouldShowSetting(addSetting, effectiveValues)) {
						result[addKey] = effectiveValues[addKey];
					}
				});
			}
		});

	return result;
	};
	
	const renderLabel = (setting, key) => {
		if (setting.label !== '') {
			return (
				<div className="waic-sidebar-label">{setting.label}
					{setting.tooltip && (
						<img className="wbw-tooltip"
							src={`${imgPath}/info.png`}
							title={setting.tooltip}
							alt="info" 
						/>
					)}
					{setting.html && (
						<div className="waic-sidebar-html" onClick={() => {
							setHtmlEditorKey(key);
							setHtmlEditorContent(node.data?.settings?.[key] ?? '');
							setHtmlEditorOpen(true);
						}}>
						HTML
						</div>
					)}
					{setting.variables && (
						<div className="waic-sidebar-variables" onClick={(e) => onOpenVariables(key)}>{lang?.['variables'] || ''}</div>
					)}
					{setting.copy && (
						<div className="waic-sidebar-copy" onClick={(e) => waicCopyText(node.data?.settings?.[key] ?? '')}>{lang?.['copy'] || ''}</div>
					)}
				</div>
			);
		}
		return null;
	};
	const renderSidebarDesc = (item) => {
		const desc = item?.desc ?? '';
		if (desc !== '') {
			return (
				<div className="waic-sidebar-desc">{desc}</div>
			);
		}
		return null;
	};
	const renderSettingField = (key, setting) => {
		const value = node.data?.settings?.[key];
		
		switch (setting.type) {
			case 'input':
				return (
					<>
						{renderLabel(setting, key)}
						<div className="waic-sidebar-field">
							<input key={key}
								type="text"
								data-field={key}
								placeholder={setting.plh ?? ''}
								value={value ?? setting.default ?? ''}
								onChange={(e) => handleSettingChange(key, e.target.value)}
								onFocus={(e) => onCursorCapture(e, key)}
								onClick={(e) => onCursorCapture(e, key)}
								onKeyUp={(e) => onCursorCapture(e, key)}
							/>
						</div>
					</>
				);
			case 'select':
				return (
					<>
						{renderLabel(setting, key)}
						<div className="waic-sidebar-field">
							<select key={key}
								value={value || ''}
								onChange={(e) => handleSettingChange(key, e.target.value)}
							>
								{(Object.entries(setting.options) || {}).map(([opt, val]) => (
									<option key={opt} value={opt}>{val}</option>
								))}
							</select>
						</div>
					</>
				);
			case 'multiple':
				return (
					<>
						{renderLabel(setting, key)}
						<div className="waic-sidebar-field">
							<select key={key}
								multiple
								value={Array.isArray(value) ? value : []}
								onChange={(e) => {
									const selected = Array.from(e.target.selectedOptions, opt => opt.value);
									handleSettingChange(key, selected);
								}}
							>
								{(Object.entries(setting.options) || {}).map(([opt, val]) => (
									<option key={opt} value={opt}>{val}</option>
								))}
							</select>
						</div>
					</>
				);
			case 'number':
				return (
					<>
						{renderLabel(setting, key)}
						<div className="waic-sidebar-field">
							<input key={key}
								type="number"
								step={setting.step || '1'}
								min={setting.min || '0'}
								max={setting.max || '100000000'}
								value={value || setting.default || '0'}
								onChange={(e) => handleSettingChange(key, e.target.value)}
							/>
						</div>
					</>
				);
			case 'datetime':
				return (
					<>
						{renderLabel(setting, key)}
						<div className="waic-sidebar-field">
							<input key={key}
								type="datetime-local"
								value={value || setting.default || ''}
								onChange={(e) => handleSettingChange(key, e.target.value)}
							/>
						</div>
					</>
				);
			case 'date':
				return (
					<>
						{renderLabel(setting, key)}
						<div className="waic-sidebar-field">
							<input key={key}
								type="date"
								value={value || setting.default || ''}
								onChange={(e) => handleSettingChange(key, e.target.value)}
							/>
						</div>
					</>
				);
			case 'time':
				return (
					<>
						{renderLabel(setting, key)}
						<div className="waic-sidebar-field">
							<input key={key}
								type="time"
								value={value || setting.default || ''}
								onChange={(e) => handleSettingChange(key, e.target.value)}
							/>
						</div>
					</>
				);
			case 'textarea':
				return (
					<>
						{renderLabel(setting, key)}
						<div className="waic-sidebar-field">
							<textarea key={key}
								data-field={key}
								rows={setting.rows || '6'}
								value={value ?? setting.default ?? ''}
								onChange={(e) => handleSettingChange(key, e.target.value)}
								onFocus={(e) => onCursorCapture(e, key)}
								onClick={(e) => onCursorCapture(e, key)}
								onKeyUp={(e) => onCursorCapture(e, key)}
							/>
						</div>
					</>
				);
			case 'readonly':
				let v = setting.default;
				const taskId = window.WAIC_WORKFLOW?.task_id || 0;
				if (taskId > 0 && setting.text) {
					v = setting.text.replace('{task_id}', taskId).replace('{node_id}', node.id);
					if (v != value) {
						handleSettingChange(key, v);
					}
				}
				return (
					<>
						{renderLabel(setting, key)}
						<div className="waic-sidebar-field">
							<input key={key}
								type="text"
								value={v}
								readonly
							/>
						</div>
					</>
				);
			default:
				return null;
		}
	};
	const shouldShowSetting = (setting, allValues) => {
		if (!setting.show) return true;

		return Object.entries(setting.show).every(([depKey, allowedValues]) =>
			allowedValues.includes(allValues?.[depKey])
		);
	};
	const getEffectiveSettingsValues = (settingsConfig, nodeDataSettings) => {
		const result = {};
		Object.entries(settingsConfig).forEach(([key, setting]) => {
			result[key] = nodeDataSettings?.[key] ?? setting.default ?? '';
		});
		return result;
	};
	const imgPath = window.WAIC_DATA.imgPath;

	return (
		<div className="waic-flow-sidebar">
		<>
			<div className="waic-sidebar-close">
				<img src={`${imgPath}/close.svg`} alt="Close" onClick={() => onClose(null)}/>
			</div>
			{step === 'category' && (
			<>
				<div className="waic-sidebar-header">
					<div className="waic-sidebar-title">
						{lang?.['сhoose']} {nodeType}
					</div>
				</div>
				<div className="waic-sidebar-body">
					<ul className="waic-sidebar-list">
					{categories.map((cat) => 
						cat.id === 'un' ? (
							cat.items.map((item) => (
								<li key={item.code} onClick={() => {
									setSelectedCategoryId(cat.id);
									handleItemSelect(item);
									}}>
									<div className="waic-sb-list-title">{item.name}</div>
									<div className="waic-sb-list-desc">{item.desc}</div>
								</li>
							))
						) : (
							<li key={cat.id} onClick={() => handleCategorySelect(cat.id)}>
								<div className="waic-sb-list-title">{cat.name}</div>
								<div className="waic-sb-list-desc">{cat.desc}</div>
							</li>
						)
					)}
					</ul>
				</div>
			</>
			)}

			{step === 'list' && (
			<>
				<div className="waic-sidebar-header">
					<div className="waic-sidebar-title waic-active-link" onClick={() => setStep('category')}>
						<i className="fa fa-angle-left"></i>{config[selectedCategoryId]?.name ?? ''}
					</div>
					{renderSidebarDesc(config[selectedCategoryId])}
					<div className="waic-sidebar-search">
						<input
							type="text"
							placeholder={lang?.['search'] + '...'}
							value={searchQuery}
							onChange={(e) => setSearchQuery(e.target.value)}
						/>
					</div>
				</div>
				<div className="waic-sidebar-body">
					<ul className="waic-sidebar-list">
						{(config[selectedCategoryId]?.list || [])
							.filter((item) =>
								item.name?.toLowerCase().includes(debouncedSearch.toLowerCase()) ||
								item.desc?.toLowerCase().includes(debouncedSearch.toLowerCase())
							)
							.map((item) => (
								<li key={item.code} onClick={() => handleItemSelect(item)}>
									<div className="waic-sb-list-title">{item?.name ?? ''}</div>
									<div className="waic-sb-list-desc">{item?.desc ?? ''}</div>
								</li>
							))
						}
					</ul>
				</div>
			</>
			)}

			{step === 'settings' && selectedItem && selectedItem.code === node.data?.code && (
			<>
				<div className="waic-sidebar-header">
					<div className="waic-sidebar-title waic-active-link" onClick={() => {
						if (!selectedCategoryId || selectedCategoryId === 'un' || selectedCategoryId === '') {
							setStep('category');
						} else {
							setStep('list');
						}}}>
						<i class="fa fa-angle-left"></i>{selectedItem?.name ?? ''}
					</div>
					{renderSidebarDesc(selectedItem)}
				</div>
				<div className="waic-sidebar-body">
					{Object.entries(selectedItem.settings || {}).map(([key, setting]) => {
						if (setting.inner) return null;

						//const currentValues = node.data?.settings || {};
						const currentValues = getEffectiveSettingsValues(selectedItem.settings, node.data?.settings);
						if (!shouldShowSetting(setting, currentValues)) return null;
						
						const addFields = setting.add?.map((addKey) => {
							const addSetting = selectedItem.settings?.[addKey];
							if (!addSetting) return null;
							if (!shouldShowSetting(addSetting, currentValues)) return null;
							
							return (
								<>
									{renderSettingField(addKey, addSetting)}
								</>
							);
						});

						return (
							<div className="waic-sidebar-setting">
								{renderSettingField(key, setting)}
								{addFields}
							</div>
						);
					})}
				</div>
			</>
			)}
		</>
		<HtmlEditorModal
			isOpen={htmlEditorOpen}
			initialContent={htmlEditorContent}
			onClose={(updatedContent) => {
			setHtmlEditorOpen(false);
			if (htmlEditorKey) {
				handleSettingChange(htmlEditorKey, updatedContent);
			}
		}}
		/>
		</div>
	);
}
