import React, { useState } from 'react';
import { useSelector, useDispatch } from 'react-redux';
import { useJobs } from '../hooks/useJobs';
import Table from './Table';
import Pagination from './Pagination';
import Button from './Button';
import Modal from './Modal';
import { cancelJob } from '../store/jobsSlice';
import { toast } from 'react-toastify';
import { Link } from 'react-router-dom';

// Helper function to format parameters
const formatParameters = (params) => {
    if (!params) return '-';
    if (Array.isArray(params)) return params.join(', ');
    if (typeof params === 'string') {
        try {
            const parsed = JSON.parse(params);
            return Array.isArray(parsed) ? parsed.join(', ') : params;
        } catch (e) {
            return params;
        }
    }
    return '-';
};

const JobTable = () => {
    const [statusFilter, setStatusFilter] = useState(null);
    const [page, setPage] = useState(1);
    const [cancelJobId, setCancelJobId] = useState(null);
    const dispatch = useDispatch();
    const { jobs, pagination, isLoading, error } = useSelector((state) => state.jobs);
    const { refetch } = useJobs(page, statusFilter);

    const columns = [
        { key: 'id', label: 'ID' },
        { key: 'class', label: 'Class' },
        { key: 'method', label: 'Method' },
        { key: 'parameters', label: 'Parameters' },
        { key: 'status', label: 'Status' },
        { key: 'attempts', label: 'Attempts' },
        { key: 'priority', label: 'Priority' },
        { key: 'scheduled_at', label: 'Scheduled At' },
        { key: 'actions', label: 'Actions' },
    ];

    const handleCancel = () => {
        dispatch(cancelJob(cancelJobId))
            .unwrap()
            .then(() => toast.success('Job cancelled successfully'))
            .catch(() => toast.error('Failed to cancel job'));
        setCancelJobId(null);
    };

    if (error) {
        toast.error(error);
    }

    return (
        <div className="container mx-auto p-4">
            <h1 className="text-2xl font-bold mb-4">Background Job Dashboard</h1>
            <div className="flex justify-between mb-4">
                <div className="flex items-center gap-4">
                    <div>
                        <label className="mr-2">Filter by Status:</label>
                        <select
                            value={statusFilter || ''}
                            onChange={(e) => {
                                setStatusFilter(e.target.value || null);
                                setPage(1);
                            }}
                            className="border rounded px-2 py-1 dark:bg-gray-700 dark:border-gray-600"
                        >
                            <option value="">All</option>
                            <option value="pending">Pending</option>
                            <option value="running">Running</option>
                            <option value="completed">Completed</option>
                            <option value="failed">Failed</option>
                        </select>
                    </div>
                    <Button onClick={refetch} variant="primary" disabled={isLoading}>
                        {isLoading ? 'Refreshing...' : 'Refresh'}
                    </Button>
                </div>
                <Button
                    as={Link}
                    to="/jobs/logs"
                    variant="primary"
                    onClick={() => console.log('Navigating to /jobs/logs')}
                >
                    View Logs
                </Button>
            </div>
            {error ? (
                <div className="text-center text-red-500">Error: {error}</div>
            ) : !Array.isArray(jobs) || jobs.length === 0 ? (
                <div className="text-center">No jobs found.</div>
            ) : (
                <>
                    <div className="relative">
                        {isLoading && (
                            <div className="absolute inset-0 bg-gray-100 bg-opacity-50 flex items-center justify-center">
                                <div className="text-gray-500">Loading...</div>
                            </div>
                        )}
                        <Table
                            columns={columns}
                            data={jobs}
                            renderRow={(job) => (
                                <tr key={job.id} className="td">
                                    <td>{job.id}</td>
                                    <td>{job.class}</td>
                                    <td>{job.method}</td>
                                    <td>{formatParameters(job.parameters)}</td>
                                    <td>{job.status}</td>
                                    <td>{job.attempts}</td>
                                    <td>{job.priority}</td>
                                    <td>{job.scheduled_at || '-'}</td>
                                    <td>
                                        {job.status === 'running' && (
                                            <Button
                                                variant="danger"
                                                onClick={() => setCancelJobId(job.id)}
                                            >
                                                Cancel
                                            </Button>
                                        )}
                                    </td>
                                </tr>
                            )}
                        />
                    </div>
                    {pagination && (
                        <Pagination
                            currentPage={pagination.current_page}
                            lastPage={pagination.last_page}
                            onPageChange={(newPage) => setPage(newPage)}
                        />
                    )}
                </>
            )}
            <Modal
                isOpen={!!cancelJobId}
                onClose={() => setCancelJobId(null)}
                onConfirm={handleCancel}
                title="Cancel Job"
                message="Are you sure you want to cancel this job? This action cannot be undone."
            />
        </div>
    );
};

export default JobTable;
