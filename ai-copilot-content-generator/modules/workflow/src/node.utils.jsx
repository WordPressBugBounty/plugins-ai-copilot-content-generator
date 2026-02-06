import { __ } from '@wordpress/i18n';

export function getNodeSublabel(data, blockType = 'triggers') {
	if (data.sublabel?.length > 0) return data.sublabel;
	if (!data.code?.length) return __('Click to choose', 'ai-copilot-content-generator');

	const item = window.WAIC_WORKFLOW?.blocks?.[blockType]?.[data.category]?.list?.find((it) => it.code === data.code);
	if (!item) return __('Click to choose', 'ai-copilot-content-generator');

	const settings = item.settings || {};
	const params = data.settings || {};
	let sublabel = '';

	item.sublabel?.forEach((code) => {
		const setting = settings[code] || {};
		const value = params[code];
		if (!value) return;

		if (setting.ldesc?.length > 0) {
			sublabel += setting.ldesc + ' ';
		}
		if (setting.ndesc && Object.keys(setting.ndesc).length > 0) {
			sublabel += (setting.ndesc[value] || '') + ' ';
		} else if (setting.options && Object.keys(setting.options).length > 0) {
			sublabel += (setting.options[value] || '') + ' ';
		} else {
			sublabel += value + ' ';
		}
		if (setting.ldesc_after?.length > 0) {
			sublabel += setting.ldesc_after + ' ';
		}
	});
	if (sublabel.length == 0) {
		sublabel = window.WAIC_WORKFLOW?.blocks?.[blockType]?.[data.category]?.name || '';
	}
	return sublabel.split('\\n').map((line, i) => (
		<div key={i}>{line}</div>
	));
}

export function validateConnection({ source, target, sourceHandle, edges, nodes, excludeEdgeId = null }) {
	//return true;
	if (source === target) return false;

	const hasOutgoingFromHandle = edges.some((e) => 
		e.source === source && e.sourceHandle === sourceHandle
	);
	if (hasOutgoingFromHandle) return false;
		
	const alreadyConnected = edges.some((e) =>
		e.source === source &&
		e.sourceHandle === sourceHandle &&
		e.target === target &&
		e.id !== excludeEdgeId
	);
	if (alreadyConnected) return false;
		
	if (hasPath(target, source, edges)) return false;

	const sourceLoop = getLoopParent(source, nodes, edges);
	const targetLoop = getLoopParent(target, nodes, edges);

	if (targetLoop !== true) {
		if (
			(sourceLoop && sourceLoop !== true && targetLoop && sourceLoop !== targetLoop) ||
			(sourceLoop && sourceLoop !== true && !targetLoop) ||
			(!sourceLoop && targetLoop)
		) return false;
	}
	const targetNode = nodes.find(n => n.id === target);
	const isTargetLoop = targetNode?.data?.category === 'lp';
	const isHandleLoop = sourceHandle == 'output-then';
	const sourceNode = nodes.find(n => n.id === source);
	const isSourceLoop = sourceNode?.data?.category === 'lp';
	const descendants = getDescendants(target, edges);
	const hasLpDescendants = descendants.some(descId => {
		const node = nodes.find(n => n.id === descId);
		return node?.data?.category === 'lp';
	});
	if ((isSourceLoop && isHandleLoop) || (sourceLoop && sourceLoop !== true)) {
		if (isTargetLoop || hasLpDescendants || (targetLoop && targetLoop !== true)) return false;
	}

	return true;
}
function getDescendants(nodeId, edges, visited = new Set()) {
	const descendants = [];
	if (visited.has(nodeId)) return descendants;
	visited.add(nodeId);

	const outgoing = edges.filter(e => e.source === nodeId);
	for (const edge of outgoing) {
		descendants.push(edge.target);
		descendants.push(...getDescendants(edge.target, edges, visited));
	}

	return descendants;
}
function getLoopParent(nodeId, nodes, edges, visited = new Set()) {
	if (visited.has(nodeId)) return false;
	const incomingEdges = edges.filter(e => e.target === nodeId);
	if (visited.size == 0 && incomingEdges.length == 0) return true;

	visited.add(nodeId);
	
	for (const edge of incomingEdges) {
		const parentNode = nodes.find(n => n.id === edge.source);
		if (!parentNode) continue;

		if (parentNode.data?.category === 'lp' && edge.sourceHandle === 'output-then') {
			return parentNode.id;
		}
		const result = getLoopParent(parentNode.id, nodes, edges, visited);
		if (result) return result;
	}

	return false;
}
function hasPath(fromId, toId, edges, visited = new Set()) {
	if (fromId === toId) return true;
	if (visited.has(fromId)) return false;
	visited.add(fromId);

	const outgoing = edges.filter(e => e.source === fromId);
	for (const edge of outgoing) {
		if (hasPath(edge.target, toId, edges, visited)) {
			return true;
		}
	}
	return false;
}
