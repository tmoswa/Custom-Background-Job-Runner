import React, { Suspense, lazy } from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter, Routes, Route } from 'react-router-dom';
import { Provider } from 'react-redux';
import { store } from './store';
import { ToastContainer } from 'react-toastify';
import 'react-toastify/dist/ReactToastify.css';

const JobTable = lazy(() => import('./components/JobTable'));
const LogViewer = lazy(() => import('./components/LogViewer'));

const App = () => (
    <Provider store={store}>
        <BrowserRouter>
            <Suspense fallback={<div className="text-center p-4">Loading...</div>}>
                <Routes>
                    <Route path="/jobs" element={<JobTable />} />
                    <Route path="/jobs/logs" element={<LogViewer />} />
                    <Route path="*" element={<div className="text-center p-4">404 - Page Not Found</div>} />
                </Routes>
            </Suspense>
        </BrowserRouter>
        <ToastContainer position="top-right" autoClose={3000} />
    </Provider>
);

const root = createRoot(document.getElementById('root'));
root.render(<App />);
