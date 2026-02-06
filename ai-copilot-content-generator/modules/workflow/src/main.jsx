import React from 'react';
import { createRoot } from 'react-dom/client';
import BuilderWrapper from './builder.jsx';
import 'reactflow/dist/style.css';
import './builder.css';

const container = document.getElementById('waic-workflow-root');
if (container) {
  const root = createRoot(container);
  root.render(<BuilderWrapper />);
}
