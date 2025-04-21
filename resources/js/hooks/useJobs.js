import { useEffect } from 'react';
import { useDispatch } from 'react-redux';
import { fetchJobs } from '../store/jobsSlice';

export const useJobs = (page, statusFilter) => {
    const dispatch = useDispatch();

    // Initial fetch and refetch function
    const refetch = () => {
        dispatch(fetchJobs({ page, status: statusFilter }));
    };

    // Fetch jobs on mount or when page/statusFilter changes
    useEffect(() => {
        refetch();
    }, [dispatch, page, statusFilter]);

    return { refetch };
};
