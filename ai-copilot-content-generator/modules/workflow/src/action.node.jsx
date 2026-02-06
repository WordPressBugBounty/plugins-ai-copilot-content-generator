import { Position, Handle } from 'reactflow';
import { getNodeSublabel } from './node.utils.jsx';
import { __ } from '@wordpress/i18n';

export default function ActionNode({ id, data }) {
	return (
		<div class="waic-flow-node waic-action-node">
			<Handle type="target" position={Position.Left} id="input-left" />
			<div class="waic-node-id">[{id}]</div>
			{data.error && (
				<div className="waic-flow-error-node">!</div>
			)}
			{!data.code && (
				<div className="waic-flow-add-icon">+</div>
			)}
			<div class="waic-node-label">{data.label ? data.label : __('Add action', 'ai-copilot-content-generator')}</div>
			<div class="waic-node-sublabel">{getNodeSublabel(data, 'actions')}</div>
			<Handle type="source" position={Position.Right} id="output-right" />
		</div>
	);
}
