import React, { useState, useEffect } from 'react';
import axios from 'axios';

const LogViewer = () => {
    const [logs, setLogs] = useState([]);

    useEffect(() => {
        axios.get('/api/jobs/logs').then(response => setLogs(response.data.logs));
    }, []);

    return (
        <div className="container mx-auto p-4">
            <h1 className="text-2xl font-bold mb-4">Background Job Logs</h1>
            <a href="/jobs" className="bg-blue-500 text-white px-4 py-2 rounded mb-4 inline-block">Back to Dashboard</a>
            <pre className="bg-gray-100 p-4 rounded">
                {logs.map((log, index) => (
                    <div key={index}>{log}</div>
                ))}
            </pre>
        </div>
    );
};

export default LogViewer;
