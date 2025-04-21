import { createSlice, createAsyncThunk } from '@reduxjs/toolkit';
import api from '../api';

export const fetchJobs = createAsyncThunk('jobs/fetchJobs', async ({ page, status }, { rejectWithValue }) => {
    try {
        const response = await api.getJobs(page, status);
        return response.data;
    } catch (error) {
        return rejectWithValue(error.response?.data?.error || 'Failed to fetch jobs');
    }
});

export const fetchLogs = createAsyncThunk('jobs/fetchLogs', async (_, { rejectWithValue }) => {
    try {
        const response = await api.getLogs();
        return response.data.logs;
    } catch (error) {
        return rejectWithValue(error.response?.data?.error || 'Failed to fetch logs');
    }
});

export const cancelJob = createAsyncThunk('jobs/cancelJob', async (id, { rejectWithValue }) => {
    try {
        await api.cancelJob(id);
        return id;
    } catch (error) {
        return rejectWithValue(error.response?.data?.error || 'Failed to cancel job');
    }
});

const jobsSlice = createSlice({
    name: 'jobs',
    initialState: {
        jobs: [], // Ensure always an array
        pagination: null,
        logs: [],
        isLoading: false,
        error: null,
    },
    reducers: {
        resetJobs: (state) => {
            state.jobs = [];
            state.pagination = null;
            state.error = null;
        },
    },
    extraReducers: (builder) => {
        builder
            .addCase(fetchJobs.pending, (state) => {
                state.isLoading = true;
                state.error = null;
            })
            .addCase(fetchJobs.fulfilled, (state, action) => {
                state.isLoading = false;
                state.jobs = action.payload.data || []; // Fallback to empty array
                state.pagination = {
                    current_page: action.payload.current_page || 1,
                    last_page: action.payload.last_page || 1,
                };
            })
            .addCase(fetchJobs.rejected, (state, action) => {
                state.isLoading = false;
                state.error = action.payload;
                state.jobs = []; // Reset to empty array on error
            })
            .addCase(fetchLogs.pending, (state) => {
                state.isLoading = true;
                state.error = null;
            })
            .addCase(fetchLogs.fulfilled, (state, action) => {
                state.isLoading = false;
                state.logs = action.payload || [];
            })
            .addCase(fetchLogs.rejected, (state, action) => {
                state.isLoading = false;
                state.error = action.payload;
            })
            .addCase(cancelJob.fulfilled, (state, action) => {
                state.jobs = state.jobs.map((job) =>
                    job.id === action.payload ? { ...job, status: 'failed' } : job
                );
            });
    },
});

export const { resetJobs } = jobsSlice.actions;
export default jobsSlice.reducer;
