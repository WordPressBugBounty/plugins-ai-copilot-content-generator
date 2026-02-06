import { __ } from '@wordpress/i18n';
import { useState, useEffect, useRef } from 'react';

export default function ControlPanel({ onAddNode, onSaveFlow }) {
	const imgPath = window.WAIC_DATA.imgPath;
	const waicWorkflow = window.WAIC_WORKFLOW || {};
	const initialTitle = waicWorkflow.title?.trim() || '';

	const globalSettings = waicWorkflow.global || {};
	const taskId = parseInt(waicWorkflow.task_id) || 0;

	const [title, setTitle] = useState((initialTitle.length > 0 ? initialTitle : waicWorkflow.lang?.title_plh) || '');
	const [showDialog, setShowDialog] = useState(false);
	const [tempTitle, setTempTitle] = useState('');
	
	const [showLog, setShowLog] = useState(false);
	const [logDate, setLogDate] = useState('');
	
	const [showSettings, setShowSettings] = useState(false);
	const [tempSettings, setTempSettings] = useState({});
	
	const [taskStatus, setTaskStatus] = useState(waicWorkflow.status || 0);
	
	const dialogLogRef = useRef(null);

	useEffect(() => {

		if (!showLog) return;
		const dialog = dialogLogRef.current;
		if (!dialog) return;

		let isDragging = false;
		let offsetX = 0;
		let offsetY = 0;

		const header = dialog.querySelector('.waic-dialog-header');

		const onMouseDown = (e) => {

			isDragging = true;
			offsetX = e.clientX - dialog.offsetLeft;
			offsetY = e.clientY - dialog.offsetTop;
			document.addEventListener('mousemove', onMouseMove);
			document.addEventListener('mouseup', onMouseUp);
		};

		const onMouseMove = (e) => {
			if (!isDragging) return;
			dialog.style.left = e.clientX - offsetX + 'px';
			dialog.style.top = e.clientY - offsetY + 'px';
		};

		const onMouseUp = () => {
			isDragging = false;
			document.removeEventListener('mousemove', onMouseMove);
			document.removeEventListener('mouseup', onMouseUp);
		};
		
		header.addEventListener('mousedown', onMouseDown);
		return () => {
			header.removeEventListener('mousedown', onMouseDown);
		};

	}, [showLog]);

	const onChangeTitle = () => {
		setTempTitle(waicWorkflow.title || '');
		setShowDialog(true);
	};

	const onConfirmTitle = () => {
		const t = tempTitle.trim() || '';
		setTitle(t);
		window.WAIC_WORKFLOW.title = t;
		setShowDialog(false);
	};

	const onCancelTitle = () => {
		setShowDialog(false);
	};
	
	const onChangeSettings = () => {
		setTempSettings(waicWorkflow.flow?.settings || {});
		setShowSettings(true);
	};
	const onCancelSettings = () => {
		setShowSettings(false);
	};
	const onConfirmSettings = () => {
		window.WAIC_WORKFLOW.flow.settings = tempSettings || {};
		setShowSettings(false);
	};
	const onShowLog = () => {
		setShowLog(true);
	};
	const onCloseLog = () => {
		setShowLog(false);
	};
	useEffect(() => {
		if (showLog) {
			getLogData('');
		}
	}, [showLog]);
	const getLogData = (date) => {
		setLogDate(date);
		const logBlock = document.querySelector('.waic-log-block');
		const loader = document.querySelector('.waic-log-loader');

		if (!logBlock || !loader) return;

		logBlock.classList.add('wbw-hidden');
		loader.classList.remove('wbw-hidden');
		jQuery.sendFormWaic({
			data: {
				task_id: taskId,
				mod: 'workflow',
				action: 'getLogData',
				date: date,
				pl: 'waic',
				reqType: 'ajax',
				waicNonce: window.WAIC_DATA.waicNonce,
			},
			onSuccess: function(res) {
				if (!res.error && res.html) {
					logBlock.innerHTML = res.html || '';
				} else {
					logBlock.innerHTML = '<div>Error loading logs</div>';
				}
				logBlock.classList.remove('wbw-hidden');
				loader.classList.add('wbw-hidden');
			}
		});
	};

	const onRunFlow = () => {
		jQuery.sendFormWaic({
			elem: jQuery('.waic-control-run'),
			data: {
				task_id: taskId,
				mod: 'workflow',
				action: waicWorkflow.status == 4 ? 'stopWorkflow' : 'runWorkflow',
				pl: 'waic',
				reqType: 'ajax',
				waicNonce: window.WAIC_DATA.waicNonce,
			},
			onSuccess: function(res) {
				if (!res.error && res.data && res.data.status) {
					window.WAIC_WORKFLOW.status = res.data.status;
					setTaskStatus(res.data.status);
				}
			}
		});
	};
	const handleSettingChange = (key, value) => {
		setTempSettings((prev) => ({
			...prev,
			[key]: value,
		}));
	};
	const renderLabel = (setting, key) => {
		if (setting.label !== '') {
			return (
				<div className="waic-dialog-label">{setting.label}
				</div>
			);
		}
		return null;
	};
	const renderSettingField = (key, setting) => {
		const value = tempSettings?.[key];
		
		switch (setting.type) {
			case 'input':
				return (
					<>
						{renderLabel(setting, key)}
						<div className="waic-dialog-field">
							<input key={key}
								type="text"
								value={value ?? setting.default ?? ''}
								onChange={(e) => handleSettingChange(key, e.target.value)}
							/>
						</div>
					</>
				);
			case 'select':
				return (
					<>
						{renderLabel(setting, key)}
						<div className="waic-dialog-field">
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
			case 'number':
				return (
					<>
						{renderLabel(setting, key)}
						<div className="waic-dialog-field">
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
	const getSettingsValues = (settingsConfig, settings) => {
		const result = {};
		Object.entries(settingsConfig).forEach(([key, setting]) => {
			result[key] = settings?.[key] ?? setting.default ?? '';
		});
		return result;
	};
	
	return (
		<div className="waic-flow-control">
			<div className="waic-flow-coltrols">
				<div className="waic-flow-title">
					{title}
				</div>
				<i className="fa fa-fw fa-pencil waic-flow-title-btn" onClick={onChangeTitle}></i>
			</div>
			<div className="waic-flow-coltrols">
				<button className="wbw-button wbw-button-small" onClick={() => onAddNode('trigger')}>
					<i className="fa fa-plus"></i> {__('Trigger', 'ai-copilot-content-generator')}
				</button>
				<button className="wbw-button wbw-button-small" onClick={() => onAddNode('action')}>
					<i className="fa fa-plus"></i> {__('Action', 'ai-copilot-content-generator')}
				</button>
				<button className="wbw-button wbw-button-small" onClick={() => onAddNode('logic')}>
					<i className="fa fa-plus"></i> {__('Logic', 'ai-copilot-content-generator')}
				</button>
				<div className="waic-flow-separator"></div>
				<button className="wbw-button wbw-button-small waic-button-icon" onClick={onShowLog}>
					<img src={`${imgPath}/log.svg`} alt="Log" />
				</button>
				<div className="waic-flow-separator"></div>
				<button className="wbw-button wbw-button-small waic-button-icon" onClick={onChangeSettings}>
					<img src={`${imgPath}/settings.svg`} alt="Settings" />
				</button>
				<div className="waic-flow-separator"></div>
				<button className="wbw-button wbw-button-small waic-button-icon waic-control-save" onClick={onSaveFlow}>
					<img src={`${imgPath}/save.svg`} alt="Save" />
				</button>
				<div className="waic-flow-separator"></div>
				<button className="wbw-button wbw-button-small wbw-button-main waic-control-run" disabled={(taskId === 0)} onClick={onRunFlow}>
					{taskStatus == 4 ? waicWorkflow.lang?.btn_stop : waicWorkflow.lang?.btn_run}
				</button>
			</div>
			{showDialog && (
				<div className="waic-dialog-overlay">
					<div className="waic-admin-dialog waic-dialog-compact">
						<div className="waic-dialog-panel">
							<div className="waic-dialog-header">
								<div className="waic-text">{window.WAIC_WORKFLOW?.lang?.title_dialog || ''}</div>
								<img className="waic-dialog-close" src={`${imgPath}/close.svg`} alt="Close" onClick={onCancelTitle} />
							</div>
							<div className="waic-dialog-body">
								<div className="waic-dialog-block">
									<input
										type="text"
										value={tempTitle}
										onChange={(e) => setTempTitle(e.target.value)}
										className="waic-dialog-input"
									/>
								</div>
							</div>
							<div className="waic-dialog-buttons">
								<button type="button" className="wbw-button wbw-button-small wbw-button-main" onClick={onConfirmTitle}>
									{waicWorkflow.lang?.btn_save || ''}
								</button>
								<button type="button" className="wbw-button wbw-button-small" onClick={onCancelTitle}>
									{waicWorkflow.lang?.btn_cancel || ''}
								</button>
							</div>
						</div>
					</div>
				</div>
			)}
			{showSettings && (
				<div className="waic-dialog-overlay">
					<div className="waic-admin-dialog waic-dialog-compact waic-dialog-mini">
						<div className="waic-dialog-panel">
							<div className="waic-dialog-header">
								<div className="waic-text">{window.WAIC_WORKFLOW?.lang?.settings_dialog || ''}</div>
								<img className="waic-dialog-close" src={`${imgPath}/close.svg`} alt="Close" onClick={onCancelSettings} />
							</div>
							<div className="waic-dialog-body">
								<div className="waic-dialog-settings">
									{Object.entries(globalSettings || {}).map(([key, setting]) => {
										if (setting.inner) return null;
							
										const currentValues = getSettingsValues(globalSettings, tempSettings);
										if (!shouldShowSetting(setting, currentValues)) return null;
							
										const addFields = setting.add?.map((addKey) => {
											const addSetting = globalSettings?.[addKey];
											if (!addSetting) return null;
											if (!shouldShowSetting(addSetting, currentValues)) return null;
								
											return (
											<>
												{renderSettingField(addKey, addSetting)}
											</>
											);
										});

										return (
											<div className="waic-dialog-setting">
												{renderSettingField(key, setting)}
												{addFields}
											</div>
										);
									})}
								</div>
							</div>
							<div className="waic-dialog-buttons waic-buttons-border">
								<button type="button" className="wbw-button wbw-button-small wbw-button-main" onClick={onConfirmSettings}>
									{waicWorkflow.lang?.btn_save || ''}
								</button>
								<button type="button" className="wbw-button wbw-button-small" onClick={onCancelSettings}>
									{waicWorkflow.lang?.btn_cancel || ''}
								</button>
							</div>
						</div>
					</div>
				</div>
			)}
			{showLog && (
				<div className="waic-dialog-overlay">
					<div className="waic-admin-dialog waic-dialog-move" ref={dialogLogRef}>
						<div className="waic-dialog-panel">
							<div className="waic-dialog-header">
								<div className="waic-text">{window.WAIC_WORKFLOW?.lang?.log_dialog || ''}</div>
								<img className="waic-dialog-close" src={`${imgPath}/close.svg`} alt="Close" onClick={onCloseLog} />
							</div>
							<div className="waic-dialog-body">
								<div className="waic-dialog-filter">
									<div className="waic-dialog-label">{window.WAIC_WORKFLOW?.lang?.date_label || ''}</div>
									<input
										type="date"
										value={logDate}
										onChange={(e) => getLogData(e.target.value)}
									/>
								</div>
								<div className="waic-log-block wbw-hidden">
								</div>
								<div className="waic-log-loader">
									<div className="waic-loader">
										<div className="waic-loader-bar bar1"></div><div className="waic-loader-bar bar2"></div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			)}
		</div>
	);
}