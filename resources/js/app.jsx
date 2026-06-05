import '../css/app.css';
import './bootstrap';

import { createRoot } from 'react-dom/client';
import App from './spa/App';

const container = document.getElementById('app');
if (container) {
    createRoot(container).render(<App />);
}
