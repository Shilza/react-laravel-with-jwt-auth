import Http from '../Http'
import * as action from '../store/actions'

export function login(credentials) {

    return dispatch => (
        new Promise((resolve, reject) => {
            Http.post('api/auth/login', credentials)
                .then(res => {
                    dispatch(action.authLogin(res.data));
                    return resolve();
                })
                .catch(err => {
                    const data = {
                        message: err.response.data.message,
                        statusCode: err.response.status,
                    };
                    return reject(data);
                })
        })
    )
}

export function me() {

    return dispatch => (
        new Promise((resolve, reject) => {
            Http.post('api/auth/me', {},
                {headers: {Authorization: `Bearer ${localStorage.getItem('access_token')}`}})
                .then(res => {
                    dispatch(action.authMe(res.data));
                    return resolve();
                })
                .catch(() => {
                    return resolve();
                })
        })
    )
}

export function refresh() {
        return new Promise((resolve, reject) => {
            Http.post('api/auth/refresh', {},
                {headers: {
                        Authorization: `Bearer ${localStorage.getItem('access_token')}`,
                        Refresh: localStorage.getItem('refresh_token'),
                        }
                })
                .then(res => {

                    localStorage.setItem('access_token', res.data.access_token);
                    localStorage.setItem('expires_in', res.data.expires_in);
                    localStorage.setItem('refresh_token', res.data.refresh_token);
                    Http.defaults.headers.common['Authorization'] = `Bearer ${localStorage.getItem('access_token')}`;

                    resolve();
                })
                .catch(err => {
                    reject(err.response.status);
                })
        })
    ;
}

export function socialLogin(data) {
    return dispatch => (
        new Promise((resolve, reject) => {
            Http.post(`api/auth/login/${data.social}/callback${data.params}`)
                .then(res => {
                    dispatch(action.authLogin(res.data));
                    return resolve();
                })
                .catch(err => {
                    const statusCode = err.response.status;
                    const data = {
                        error: null,
                        statusCode,
                    };
                    if (statusCode === 401 || statusCode === 422) {
                        // status 401 means unauthorized
                        // status 422 means unprocessable entity
                        data.error = err.response.data.message;
                    }
                    return reject(data);
                })
        })
    )
}

export function resetPassword(credentials) {
    return dispatch => (
        new Promise((resolve, reject) => {
            Http.post('api/auth/password/create', credentials)
                .then(res => {
                    return resolve(res.data);
                })
                .catch(err => {
                    const statusCode = err.response.status;
                    const data = {
                        message: err.response.data.message,
                        statusCode,
                    };
                    return reject(data);
                })
        })
    )
}

export function updatePassword(credentials) {
    return dispatch => (
        new Promise((resolve, reject) => {
            Http.post('../../api/auth/password/reset', credentials)
                .then(res => {
                    return resolve(res.data.message);
                })
                .catch(err => {
                    const data = {
                        message: err.response.data.message,
                        statusCode: err.response.status,
                    };

                    return reject(data);
                })
        })
    )
}

export function register(credentials) {
    return dispatch => (
        new Promise((resolve, reject) => {
            Http.post('api/auth/register', credentials)
                .then(res => {
                    return resolve(res.data);
                })
                .catch(err => {
                    const statusCode = err.response.status;
                    const data = {
                        error: null,
                        statusCode,
                    };
                    if (statusCode === 422) {
                        Object.values(err.response.data.message).map((value, i) => {
                            data.error = value
                        });

                    } else if (statusCode === 400) {
                        data.error = err.response.data.message;
                    }
                    return reject(data);
                })
        })
    )
}