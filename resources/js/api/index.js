import axios from 'axios';
import { getJobs, getLogs, cancelJob } from './jobs';

// Singleton API service
class ApiService {
    constructor() {
        if (!ApiService.instance) {
            this.axios = axios.create({
                baseURL: '/api',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                },
            });
            ApiService.instance = this;
        }
        return ApiService.instance;
    }

    // Facade methods
    getJobs(page, status = null) {
        return getJobs(this.axios, page, status);
    }

    getLogs() {
        return getLogs(this.axios);
    }

    cancelJob(id) {
        return cancelJob(this.axios, id);
    }
}

const instance = new ApiService();
Object.freeze(instance);
export default instance;
