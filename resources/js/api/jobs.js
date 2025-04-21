import axios from 'axios';

axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').content;

export const getJobs = (page) => axios.get(`/api/jobs?page=${page}`);
export const getLogs = () => axios.get('/api/jobs/logs');
export const cancelJob = (id) => axios.delete(`/api/jobs/${id}`);
