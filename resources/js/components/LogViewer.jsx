import React, { useState } from 'react';
import { useLogs } from '../hooks/useLogs';
import Button from './Button';
import DOMPurify from 'dompurify';
import { Link } from 'react-router-dom'; // Add Link import

const LogViewer = () => {
    const [filter, setFilter] = useState('');
    const { logs, isLoading, error, refetch } = useLogs();

    console.log({ logs, isLoading, error }); // Debug state

    const filteredLogs = Array.isArray(logs)
        ? logs.filter((log) => log.toLowerCase().includes(filter.toLowerCase()))
        : [];

    return (
        <div className="container mx-auto p-4">
            <h1 className="text-2xl font-bold mb-4">Background Job Logs</h1>
            <div className="flex justify-between mb-4">
                <div>
                    <label className="mr-2">Filter Logs:</label>
                    <input
                        type="text"
                        value={filter}
                        onChange={(e) => setFilter(e.target.value)}
                        className="border rounded px-2 py-1 dark:bg-gray-700 dark:border-gray-600"
                        placeholder="Search logs..."
                    />
                </div>
                <div className="flex gap-2">
                    <Button onClick={refetch} variant="primary">
                        Refresh
                    </Button>
                    <Button as={Link} to="/jobs" variant="primary"> {/* Use Link */}
                        Back to Dashboard
                    </Button>
                </div>
            </div>
            {isLoading ? (
                <div className="text-center">Loading...</div>
            ) : error ? (
                <div className="text-center text-red-500">Error: {error}</div>
            ) : !filteredLogs.length ? (
                <div className="text-center">No logs found.</div>
            ) : (
                <pre className="bg-gray-100 dark:bg-gray-800 p-4 rounded-lg overflow-auto max-h-[70vh]">
                    {filteredLogs.map((log, index) => (
                        <div
                            key={index}
                            dangerouslySetInnerHTML={{ __html: DOMPurify.sanitize(log) }}
                            className="mb-2"
                        />
                    ))}
                </pre>
            )}
        </div>
    );
};

export default LogViewer;
