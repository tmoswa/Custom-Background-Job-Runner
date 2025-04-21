import React from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter, Routes, Route } from 'react-router-dom';
import JobTable from './components/JobTable';
import LogViewer from './components/LogViewer';
import '../css/app.scss';

const App = () => (
    <BrowserRouter>
        <Routes>
            <Route path="/jobs" element={<JobTable />} />
            <Route path="/jobs/logs" element={<LogViewer />} />
        </Routes>
    </BrowserRouter>
);

const root = createRoot(document.getElementById('root'));
root.render(<App />);
