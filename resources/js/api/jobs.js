export const getJobs = (axios, page, status = null) => {
    const params = { page };
    if (status) params.status = status;
    return axios.get('/jobs', { params });
};

export const getLogs = (axios) => axios.get('/jobs/logs');

export const cancelJob = (axios, id) => axios.delete(`/jobs/${id}`);
