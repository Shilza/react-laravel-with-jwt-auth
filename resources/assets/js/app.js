import React from 'react'
import {render} from 'react-dom'
import {Provider} from 'react-redux'
import store from './store'
import Routes from './routes'
import AuthService from './services';

async function start() {

    if(localStorage.hasOwnProperty('access_token'))
        await store.dispatch(AuthService.me());

    render(
        <Provider store={store}>
            <Routes/>
        </Provider>,
        document.getElementById('root')
    );
}

start();


