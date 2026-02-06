import { useEffect, useRef } from 'react';

export default function HtmlEditorModal({ isOpen, initialContent, onClose }) {
	const editorId = 'waic-html-editor';
	const dialogRef = useRef(null);

	useEffect(() => {
		if (!isOpen) return;

		window.WAIC_WORKFLOW?.editor?.remove(editorId);

		const textarea = document.getElementById(editorId);
		if (!textarea || !window.WAIC_WORKFLOW?.editor?.initialize) return;

		window.WAIC_WORKFLOW.editor.initialize(editorId, {
			tinymce: {
				wpautop: true,
				toolbar1: 'formatselect,bold,italic,bullist,numlist,blockquote,alignleft,aligncenter,alignright,forecolor,link,undo,redo',
				height: 200
			},
			quicktags: true,
			mediaButtons: true,
		});
		waicSetTxtEditorVal(editorId, initialContent || '');

		return () => {
			window.WAIC_WORKFLOW?.editor?.remove(editorId);
		};
	}, [isOpen, initialContent]);

	if (!isOpen) return null;

	return (
		<div className="waic-dialog-overlay">
			<div className="waic-admin-dialog" ref={dialogRef}>
				<div className="waic-dialog-panel">
					<div className="waic-dialog-header">
						<div className="waic-text">
							{window.WAIC_WORKFLOW?.lang?.editor_dialog}
						</div>
						<img
							className="waic-dialog-close"
							src={`${window.WAIC_DATA?.imgPath}/close.svg`}
							alt="Close"
							onClick={() => onClose(initialContent)}
						/>
					</div>

					<div className="waic-dialog-body">
						<div className="wp-media-buttons"></div>

						<textarea id={editorId} className="waic-html-editor" />
					</div>
					<div className="waic-dialog-buttons">
						<button type="button" className="wbw-button wbw-button-small wbw-button-main" 
							onClick={() => {
							  window.tinyMCE?.triggerSave();
							  const updated = document.getElementById(editorId)?.value || '';
							  onClose(updated);
							}}
						>
							{window.WAIC_WORKFLOW?.lang?.btn_save || ''}
						</button>
						<button type="button" className="wbw-button wbw-button-small" onClick={() => onClose(initialContent)}>
							{window.WAIC_WORKFLOW?.lang?.btn_cancel || ''}
						</button>
					</div>
				</div>
			</div>
		</div>
	);
}
