import * as ActionTypes from '../action-types'
import Http from '../../Http'
import {authRefresh} from "../actions";

const user = {
    id: null,
    name: null,
    email: null,
};

const initialState = {
    isAuthenticated : false,
    user
};

const Auth = (state = initialState, {type, payload = null}) => {

    switch(type){
        case ActionTypes.AUTH_LOGIN:
            return authLogin(state,payload);
        case ActionTypes.AUTH_LOGOUT:
            return logout(state);
        case ActionTypes.AUTH_ME:
            return authMe(state, payload);
        default:
            return state;
    }
};

const authLogin = (state, payload) => {

    const accessToken = payload.access_token;
    const user = payload.user;

    localStorage.setItem('access_token', accessToken);
    localStorage.setItem('refresh_token', payload.refresh_token);
    localStorage.setItem('expires_in', payload.expires_in);

    Http.defaults.headers.common['Authorization'] = `Bearer ${accessToken}`;
    state = Object.assign({}, state, {
        isAuthenticated: true,
        user
    });

    return state;
};

const authMe = (state, payload) => {

    const user = payload.user;

    Http.defaults.headers.common['Authorization'] = `Bearer ${localStorage.getItem('access_token')}`;
    state = Object.assign({}, state, {
        isAuthenticated: true,
        user
    });

    return state;
};

const logout = (state) => {
    localStorage.removeItem('access_token');
    localStorage.removeItem('refresh_token');
    localStorage.removeItem('expires_in');

    state = Object.assign({}, state, {
        isAuthenticated: false,
        user
    });

    return state;
};

export default Auth;
