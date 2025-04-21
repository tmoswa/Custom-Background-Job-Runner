import { useEffect } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import { fetchLogs } from '../store/jobsSlice';

export const useLogs = () => {
    const dispatch = useDispatch();
    const { logs, isLoading, error } = useSelector((state) => state.jobs);

    const refetch = () => {
        dispatch(fetchLogs());
    };

    useEffect(() => {
        refetch();
    }, [dispatch]);

    return { logs: Array.isArray(logs) ? logs : [], isLoading, error, refetch };
};
