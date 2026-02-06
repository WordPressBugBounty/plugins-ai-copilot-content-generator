import React, { useRef, useState, useCallback, useEffect } from 'react';
import ReactFlow, {
  applyNodeChanges,
  applyEdgeChanges,
  addEdge,
} from 'reactflow';
import { nodeTypes, edgeTypes } from './element.types.js';
import { getNodeSublabel, validateConnection } from './node.utils.jsx';
import ControlPanel from './control.panel.jsx';
import SidebarPanel from './sidebar.panel.jsx';
import VariablesDialog from './variables.dialog.jsx';
import { nanoid } from 'nanoid';
import { Background, Controls, MiniMap, Panel, MarkerType, reconnectEdge } from 'reactflow';

const initialNodes = window.WAIC_WORKFLOW.flow?.nodes || [];
//const initialNodes = [{ id: '1', type: 'trigger', position: { x: 350, y: 200 }, data: {} }];
const initialEdges = window.WAIC_WORKFLOW.flow?.edges || [];
const savedViewport = window.WAIC_WORKFLOW.flow?.viewport || false;
let nodeCounter = Math.max(0,...initialNodes.map(n => parseInt(n.id.replace(/\D/g, '')) || 0)) + 1;
let edgeCounter = Math.max(0,...initialEdges.map(n => parseInt(n.id.replace(/\D/g, '')) || 0)) + 1;
window.WAIC_WORKFLOW.editor = wp.editor || {};
if (typeof _.isArray !== 'function') {
  _.isArray = Array.isArray;
}

export default function BuilderWrapper() {
	const [nodes, setNodes] = useState(initialNodes);
	const [edges, setEdges] = useState(initialEdges);
	const [selectedNode, setSelectedNode] = useState(null);
	const waicWorkflow = window.WAIC_WORKFLOW || {};
	const taskId = parseInt(waicWorkflow.task_id) || 0;
	
	const [showVariables, setShowVariables] = useState(false);
	const [variables, setVariables] = useState([]);
	const [activeField, setActiveField] = useState(null);

	function collectVariablesForNode(nodeId, nodes, edges) {
		const visited = new Set();
		const groups = [];
		const blocks = window.WAIC_WORKFLOW?.blocks || {};

		function traverse(currentId, incomingHandle = null, skipSelf = false) {
			if (visited.has(currentId)) return;
			visited.add(currentId);

			const node = nodes.find(n => n.id === currentId);
			if (node && !skipSelf) {
				const nodeType = node.type;
				const nodeCode = node.data?.code;
				const nodeCat = node.data?.category;
				const list = blocks[nodeType + 's']?.[nodeCat]?.list || [];
				const block = list.find((item) => item.code === nodeCode);
				const variables = block?.variables || [];
				
				const filteredVars = {};

				Object.entries(variables).forEach(([key, val]) => {
					if (key === 'loop_vars') {
						if (incomingHandle === 'output-then') {
							Object.entries(val).forEach(([loopKey, loopLabel]) => {
							filteredVars[loopKey] = loopLabel;
						});
					}
					} else {
						filteredVars[key] = val;
					}
				});
			
				if (Object.keys(filteredVars).length > 0) {
					groups.push({
						nodeId: node.id,
						label: node.data?.label,
						sublabel: getNodeSublabel(node.data, nodeType + 's'),
						variables: filteredVars,
					});
				}
			}
			const parentEdges = edges.filter(e => e.target === currentId);
			parentEdges.forEach(e => traverse(e.source, e.sourceHandle));
		}
		traverse(nodeId, null, true);
		return groups;
	}
	const cursorRef = useRef(null);
	const handleCursorCapture = (e, fieldKey) => {
		cursorRef.current = {
			element: e.target,
			start: e.target.selectionStart,
			end: e.target.selectionEnd,
			fieldKey: fieldKey,
		};
	};
	const handleOpenVariables = (fieldKey) => {
		setActiveField(fieldKey);
		if (selectedNode) {
			const vars = collectVariablesForNode(selectedNode.id, nodes, edges);
			setVariables(vars);
		}
		setShowVariables(true);
	};

	const [settingChanger, setSettingChanger] = useState(null);

	const handleInsertVariable = (id, val, customKey) => {
		if (!settingChanger || !activeField) return;
		const metaKey = customKey ? '['+customKey+']' : '';
		const variableText = `{{node#${id}.${val}${metaKey}}}`;

		if (cursorRef.current?.fieldKey === activeField) {
			const el = cursorRef.current.element;
			const start = cursorRef.current.start;
			const end = cursorRef.current.end;
			const value = el.value;

			const newValue = value.slice(0, start) + variableText + value.slice(end);
			settingChanger(activeField, newValue);
			setTimeout(() => {
				el.focus();
				const newPos = start + variableText.length;
				el.setSelectionRange(newPos, newPos);
			}, 0);
		} else {
			const currentValue = selectedNode.data?.settings?.[activeField] || '';
			settingChanger(activeField, currentValue + variableText);
		}
	};
	
	const handleAddNode = useCallback((typeNode) => {
		const id = `${nodeCounter++}`;
		const nodesLen = nodes.length || 0;
		let x = 350;
		let y = 200;

		if (selectedNode) {
			x = selectedNode.position.x + 180 + nodesLen * 5;
			y = selectedNode.position.y;
		} else if (nodesLen > 0) {
			const lastNode = nodes[nodes.length-1];
			x = lastNode.position.x + 180;
			y = lastNode.position.y;
		}
		const newNode = {
			id,
			data: {},
			type: typeNode,
			position: { x, y },
		};
		setNodes((nds) => [...nds, newNode]);
	}, [nodes, selectedNode]);
	const handleDeleteNode = useCallback((id) => {
		setNodes((nds) => nds.filter((node) => node.id !== id));
		setEdges((eds) => eds.filter((edge) => edge.source !== id && edge.target !== id));
		setSelectedNode((sel) => (sel?.id === id ? null : sel));
	}, []);

	const onNodesChange = useCallback(
		(changes) => setNodes((ns) => applyNodeChanges(changes, ns)),
		[]
	);
	const onEdgesChange = useCallback(
		(changes) => setEdges((es) => applyEdgeChanges(changes, es)),
		[]
	);
	
	const onConnect = useCallback((params) => {
		const isValid = validateConnection({
			source: params.source,
			target: params.target,
			sourceHandle: params.sourceHandle,
			edges,
			nodes,
		});

		if (!isValid) return;

		const newEdge = {
			id: `${edgeCounter++}`,
			source: params.source,
			target: params.target,
			sourceHandle: params.sourceHandle,
			targetHandle: params.targetHandle,
			type: 'default',
		};

		setEdges((es) => [...es, newEdge]);
	}, [edges]);
	
	const onReconnect = useCallback((oldEdge, newConnection) => {
		
		const edgesWithoutOld = edges.filter(e => e.id !== oldEdge.id);

		const isValid = validateConnection({
			source: newConnection.source,
			target: newConnection.target,
			sourceHandle: newConnection.sourceHandle,
			edges: edgesWithoutOld,
			nodes,
		});

		if (!isValid) return;

		setEdges((els) => reconnectEdge(oldEdge, newConnection, els));
	}, [edges, nodes]);

	const onNodeClick = useCallback((event, node) => {
		setSelectedNode(node);
	}, []);
	
	const handleNodeDragStop = (event, node) => {
		updateNode({
		...node,
		position: node.position,
		positionAbsolute: node.positionAbsolute,
		data: {
			...node.data,
			dragged: true, 
		},
		});
	};

	const updateNode = (updatedNode) => {
		if (!updatedNode) {
		setSelectedNode(null);
		return;
		}
		
		setNodes((prevNodes) => {
			const oldNode = prevNodes.find((n) => n.id === updatedNode.id);
			const oldCode = oldNode?.data?.code;
			const newCode = updatedNode.data?.code;
			const nodeType = updatedNode.type;

			const shouldCleanEdges = (nodeType === 'logic' && oldCode !== newCode);

			if (shouldCleanEdges) {
				setEdges((prevEdges) =>
					prevEdges.filter((edge) => {
						if (edge.source !== updatedNode.id) return true;
						if (newCode === 'un_delay') {
							return !['output-then', 'output-else'].includes(edge.sourceHandle);
						} else {
							return !['output-right'].includes(edge.sourceHandle);
						}
					})
				);
			}

			return prevNodes.map((n) => (n.id === updatedNode.id ? updatedNode : n));
		});
		
		setSelectedNode(updatedNode);
	};
	const [selectedEdges, setSelectedEdges] = useState([]);

	const onSelectionChange = useCallback(({ nodes = [], edges = [] }) => {
		setSelectedEdges(edges);

		if (nodes.length === 1) {
			setSelectedNode(nodes[0]); // открываем сайдбар
		} else {
			setSelectedNode(null); // скрываем сайдбар, если ничего не выбрано
		}
	}, []);
	
	const serializeFlow = () => {
		const { x, y, zoom } = reactFlowInstanceRef.current.getViewport();
		return {
			nodes: nodes.map(({ id, type, position, data }) => ({
				id, type, position, data,
			})),
			edges: edges.map(({ id, source, target, sourceHandle, targetHandle, type }) => ({
				id, source, target, sourceHandle, targetHandle, type,
			})),
			viewport: { x, y, zoom },
			settings: waicWorkflow.flow?.settings || '',
			version: '1.0.0',
		};
	};
	const handleSaveFlow = () => {
		jQuery.sendFormWaic({
			imgBtn: jQuery('.waic-control-save'),
			data: {
				flow: JSON.stringify(serializeFlow()),
				task_id: taskId,
				title: waicWorkflow.title || '',
				mod: 'workflow',
				action: 'saveWorkflow',
				pl: 'waic',
				reqType: 'ajax',
				waicNonce: window.WAIC_DATA.waicNonce,
				//params: jsonInputsWaic($form, true),
			},
			onSuccess: function(res) {
				if (!res.error && res.data && res.data.taskUrl) {
					if (taskId == 0) jQuery(location).attr('href', res.data.taskUrl);
				}
				if (res.error && res.data && res.data.err_nodes) {
					//WAIC_WORKFLOW.errors=[res.data.err_nodes];
					res.data.err_nodes.map(n => 
						setNodes(prev =>
							prev.map(node =>
								node.id === n[0]
								? { ...node, data: { ...node.data, error: n[1] } }
								: node
							)
						)
					);
					/*if (selectedNode) {
						setSelectedNode(null);
					}*/
				}
			}
		});
	};

	useEffect(() => {
		const handleKeyDown = (e) => {
			const isDeleteKey = e.key === 'Delete' || e.key === 'Backspace';
			if (!isDeleteKey) return;
			const el = document.activeElement;
			const tag = el?.tagName?.toUpperCase();

			const isFormElement =
				tag === 'INPUT' ||
				tag === 'TEXTAREA' ||
				tag === 'SELECT' ||
				tag === 'BUTTON' ||
				el?.isContentEditable;
			if (isFormElement) return;

			if (selectedEdges.length > 0) {
			  setEdges((eds) =>
				eds.filter((edge) => !selectedEdges.some((sel) => sel.id === edge.id))
			  );
			}

			if (selectedNode) {
				setNodes((nds) => nds.filter((node) => node.id !== selectedNode.id));
				setEdges((eds) =>
					eds.filter(
						(edge) =>
							edge.source !== selectedNode.id && edge.target !== selectedNode.id
					)
				);
				setSelectedNode(null);
			}
		};

		window.addEventListener('keydown', handleKeyDown);
		return () => window.removeEventListener('keydown', handleKeyDown);
	}, [selectedEdges, selectedNode]);
	const reactFlowInstanceRef = useRef(null);
	
	return (
		<>
			<ControlPanel onAddNode={handleAddNode} onSaveFlow={handleSaveFlow} />
			<div id="waic-flow-canvas">
				<ReactFlow
					nodes={nodes}
					edges={edges}
					nodeTypes={nodeTypes}
					edgeTypes={edgeTypes}
					onNodesChange={onNodesChange}
					onEdgesChange={onEdgesChange}
					onSelectionChange={onSelectionChange}
					onConnect={onConnect}
					onReconnect={onReconnect}
					onNodeClick={onNodeClick}
					onNodeDragStop={handleNodeDragStop}
					minZoom={0.3}
					maxZoom={1.5}
					onInit={(instance) => {
						reactFlowInstanceRef.current = instance;
						if (savedViewport) {
							instance.setViewport(savedViewport);
						}
					}}
				>
					<Background />
					<Controls position="bottom-right" />
				</ReactFlow>
				{selectedNode && (
				<>
					<SidebarPanel 
						node={selectedNode} 
						onClose={updateNode} 
						onOpenVariables={handleOpenVariables} 
						onCursorCapture={handleCursorCapture}
						exposeHandle={(fn) => {setSettingChanger(() => fn);}} 
					/>
					<VariablesDialog open={showVariables}
						variables={variables}
						onSelect={handleInsertVariable}
						onClose={() => setShowVariables(false)}
					/>
				</>
				)}
			</div>
		</>
	);
}
