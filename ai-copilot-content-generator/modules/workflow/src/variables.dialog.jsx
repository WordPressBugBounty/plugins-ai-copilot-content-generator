import React, { useRef, useState, useEffect } from 'react';

export default function VariablesDialog({ open, variables, onSelect, onClose }) {
	const dialogRef = useRef(null);
	const [metaKeys, setMetaKeys] = useState({});

	useEffect(() => {
		if (!open) return;
		const dialog = dialogRef.current;
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
		
		const handleClickOutside = (e) => {
			if (!dialog.contains(e.target)) {
				onClose();
			}
		};
		waicInitTooltips('.waic-group-list');

		header.addEventListener('mousedown', onMouseDown);
		document.addEventListener('mousedown', handleClickOutside);
		return () => {
			header.removeEventListener('mousedown', onMouseDown);
			document.removeEventListener('mousedown', handleClickOutside);
		};

	}, [open, onClose]);
	
	const handleSelect = (nodeId, varKey, isMeta) => {
		const customKey = isMeta ? metaKeys[`${nodeId}_${varKey}`] : null;
		onSelect(nodeId, varKey, customKey);
		setMetaKeys(prev => ({ ...prev, [`${nodeId}_${varKey}`]: '' }));
	};

	const handleMetaChange = (nodeId, varKey, value) => {
		setMetaKeys(prev => ({ ...prev, [`${nodeId}_${varKey}`]: value }));
	};

	/*const handleSelect = (nodeId, varKey, isMeta) => {
		const customKey = isMeta ? metaKey : null;
		onSelect(nodeId, varKey, metaKey);
		setMetaKey(null);
	};*/
  
	if (!open) return null;
	return (
		<div className="waic-dialog-overlay">
			<div 
				ref={dialogRef}
				className="waic-admin-dialog waic-dialog-compact waic-dialog-move"
			>
				<div className="waic-dialog-panel">
					<div className="waic-dialog-header">
						<div className="waic-dialog-title">{window.WAIC_WORKFLOW?.lang?.var_dialog || ''}</div>
						<img className="waic-dialog-close" src={`${window.WAIC_DATA.imgPath}/close.svg`} alt="Close" onClick={onClose} />
					</div>
					<div className="waic-dialog-body">
						<div className="waic-dialog-block">
						{variables.length > 0 ? (
							
							<ul className="waic-group-list">
							{variables.map(group => (
								<li key={group.nodeId} className="waic-variable-group">
									<div className="waic-group-title">{group.label} <div className="waic-group-sublabel">{group.sublabel}</div> <div className="waic-group-id">[{group.nodeId}]</div></div>
									<ul className="waic-variables-list">
									{Object.entries(group.variables).map(([key, val]) => {
										const isMeta = val.endsWith('*');
										//const isField = val.endsWith('***');
										const tooltip = isMeta ? window.WAIC_WORKFLOW?.lang?.['var_tooltip_' + key] || '' : '';
										const placeholder = isMeta ? window.WAIC_WORKFLOW?.lang?.['var_plh_' + key] || '' : '';
										//const placeholder = isField ? window.WAIC_WORKFLOW?.lang?.plh_field : ( val.endsWith('**') ? window.WAIC_WORKFLOW?.lang?.plh_taxonomy : window.WAIC_WORKFLOW?.lang?.plh_meta );
										
										return (
											<li key={key}>
												<button onClick={() => handleSelect(group.nodeId, key, isMeta)}>
													{val.replace(/\*/g, '')}
												</button>
												{tooltip && (
													<img className="wbw-tooltip"
														src={`${window.WAIC_DATA.imgPath}/info.png`}
														title={tooltip}
														alt="info" 
													/>
												)}
												{isMeta && (
													<input 
														type="text"
														placeholder={placeholder || ''}
														className="waic-variable-meta"
														value={metaKeys[`${group.nodeId}_${key}`] || ''}
														onChange={(e) => handleMetaChange(group.nodeId, key, e.target.value)}
													/>
												)}
											</li>
										);
									})}
									</ul>
								</li>
							))}
							</ul>
						) : (
							window.WAIC_WORKFLOW?.lang?.var_none || 'No variables'
						)}
						</div>
					</div>
				</div>
			</div>
		</div>
	);
}