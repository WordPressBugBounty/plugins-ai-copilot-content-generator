import { getBezierPath } from 'reactflow';

export default function WaicDefaultEdge({ id, sourceX, sourceY, targetX, targetY, sourcePosition, targetPosition, selected }) {
	const [edgePath] = getBezierPath({
		sourceX,
		sourceY,
		sourcePosition,
		targetX,
		targetY,
		targetPosition,
	});
	const strokeColor = selected ? '#000000' : '#9197b3';
	return (
	<>
		<path
			d={edgePath}
			stroke="transparent"
			strokeWidth={selected ? 1 : 15}
			fill="none"
			style={{
				pointerEvents: 'stroke',
				cursor: 'pointer',
			}}
		/>

		{/* Видимая линия */}
		<path
			id={id}
			d={edgePath}
			stroke={strokeColor}
			strokeWidth={1}
			fill="none"
			markerEnd="url(#arrow-default)"
			style={{
				pointerEvents: 'none',
			}}
		  />

		  <defs>
			<marker
				id="arrow-default"
				markerWidth="6"
				markerHeight="6"
				refX="6"
				refY="3"
				orient="auto"
				markerUnits="strokeWidth"
			>
				<path d="M0,0 L6,3 L0,6 Z" fill="#9197b3" />
			</marker>
		  </defs>
	</>
	);
}