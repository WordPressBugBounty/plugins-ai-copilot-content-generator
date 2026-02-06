import { Position, Handle } from 'reactflow';
import { getNodeSublabel } from './node.utils.jsx';
import { __ } from '@wordpress/i18n';

export default function LogicNode({ id, data }) {
	const stopNode = data.code === 'un_stop' || data.code === 'un_stop_loop';
	return (
		<div className="waic-flow-node waic-logic-node">
			<Handle type="target" position={Position.Left} id="input-left" />
			<div className="waic-node-id">[{id}]</div>
			{data.error && (
				<div className="waic-flow-error-node">!</div>
			)}
			{!data.code && (
				<div className="waic-flow-add-icon">+</div>
			)}
			<div className="waic-node-label">{data.label ? data.label : __('Add logic', 'ai-copilot-content-generator')}</div>
			<div className="waic-node-sublabel">{getNodeSublabel(data, 'logics')}</div>
			{data.code === 'un_branch' && data.category !== 'lp' && (
				<>
					<div className="waic-handle-then">{__('THEN', 'ai-copilot-content-generator')}</div>
					<div className="waic-handle-else">{__('ELSE', 'ai-copilot-content-generator')}</div>
				</>
			)}
			{data.category === 'lp' && data.code !== 'un_branch' && (
				<>
					<div className="waic-handle-then">{__('LOOP', 'ai-copilot-content-generator')}</div>
					<div className="waic-handle-else">{__('END', 'ai-copilot-content-generator')}</div>
				</>
			)}

			<Handle type="source" position={Position.Right} id="output-then" style={{ top: '15%' }} isConnectableStart={data.code !== 'un_delay' && !stopNode} />
			<Handle type="source" position={Position.Right} id="output-else" style={{ top: '85%' }} isConnectableStart={data.code !== 'un_delay' && !stopNode} />

			<Handle type="source" position={Position.Right} id="output-right" style={{ top: '50%' }} isConnectableStart={data.code === 'un_delay' && !stopNode} />

		</div>
	);
}
