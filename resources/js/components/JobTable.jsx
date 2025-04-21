import React, { useState, useEffect } from 'react';
import axios from 'axios';

const JobTable = () => {
    const [jobs, setJobs] = useState([]);
    const [page, setPage] = useState(1);

    useEffect(() => {
        fetchJobs();
    }, [page]);

    const fetchJobs = async () => {
        const response = await axios.get(`/api/jobs?page=${page}`);
        setJobs(response.data.data);
    };

    const cancelJob = async (id) => {
        await axios.delete(`/api/jobs/${id}`);
        fetchJobs();
    };

    return (
        <div className="container mx-auto p-4">
            <h1 className="text-2xl font-bold mb-4">Background Job Dashboard</h1>
            <a href="/jobs/logs" className="bg-blue-500 text-white px-4 py-2 rounded mb-4 inline-block">View Logs</a>
            <table className="w-full border-collapse">
                <thead>
                <tr className="bg-gray-200">
                    <th className="p-2">ID</th>
                    <th className="p-2">Class</th>
                    <th className="p-2">Method</th>
                    <th className="p-2">Status</th>
                    <th className="p-2">Attempts</th>
                    <th className="p-2">Priority</th>
                    <th className="p-2">Scheduled At</th>
                    <th className="p-2">Actions</th>
                </tr>
                </thead>
                <tbody>
                {jobs.map(job => (
                    <tr key={job.id} className="border-b">
                        <td className="p-2">{job.id}</td>
                        <td className="p-2">{job.class}</td>
                        <td className="p-2">{job.method}</td>
                        <td className="p-2">{job.status}</td>
                        <td className="p-2">{job.attempts}</td>
                        <td className="p-2">{job.priority}</td>
                        <td className="p-2">{job.scheduled_at || '-'}</td>
                        <td className="p-2">
                            {job.status === 'running' && (
                                <button
                                    onClick={() => cancelJob(job.id)}
                                    className="bg-red-500 text-white px-2 py-1 rounded"
                                >
                                    Cancel
                                </button>
                            )}
                        </td>
                    </tr>
                ))}
                </tbody>
            </table>
        </div>
    );
};

export default JobTable;
