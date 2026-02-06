import TriggerNode from './trigger.node.jsx';
import ActionNode from './action.node.jsx';
import LogicNode from './logic.node.jsx';
import WaicDefaultEdge from './edge.default.jsx';

export const nodeTypes = {
	trigger: TriggerNode,
	action: ActionNode,
	logic: LogicNode,
};

export const edgeTypes = {
	default: WaicDefaultEdge,
};