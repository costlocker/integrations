import React from 'react';

import { appState, isNotLoggedInCostlocker } from './state';
import { fetchFromApi } from './api';
import Login from './app/Login';

export let redirectToRoute = (route) => console.log('app is not ready', route);
export let isRouteActive = () => false;
const setError = e => appState.cursor(['app']).set('error', e);

const fetchUser = () => {
  const data = appState.cursor(['login']).deref();
  if (!data.get('host') || !data.get('token')) {
    appState.cursor().update(
      app => app
        .setIn(['auth', 'isLoading'], false)
        .setIn(['auth', 'costlocker'], null)
        .setIn(['login', 'error'], null)
    );
    return;
  }
  fetchFromApi(`/me`)
    .catch(() => {
      appState.cursor().update(
        app => app
          .setIn(['auth', 'isLoading'], false)
          .setIn(['auth', 'costlocker'], null)
          .setIn(['login', 'error'], 'Invalid Token')
      );
    })
    .then((user) => {
      if (!user || !user.data) {
        return;
      }
      appState.cursor().update(
        app => app
          .setIn(['auth', 'isLoading'], false)
          .setIn(['auth', 'costlocker'], user.data)
          .setIn(['login', 'error'], null)
      );
    });
}

if (isNotLoggedInCostlocker()) {
  fetchUser();
}

export const states = [
  {
    name: 'homepage',
    url: '/',
    redirectTo: 'login',
  },
  {
    name: 'login',
    url: '/login',
    data: {
      title: 'Login'
    },
    component: (props) => <Login
      costlockerAuth={appState.cursor(['auth', 'costlocker']).deref()}
      form={{
        get: (type) => appState.cursor(['login', type]).deref(),
        set: (type) => (e) => appState.cursor(['login']).set(
          type,
          e.target.type === 'checkbox' ? e.target.checked : e.target.value
        ),
        submit: (e) => {
          e.preventDefault();
          fetchUser();
        },
      }} />,
  },
];

const hooks = [
  {
    event: 'onBefore',
    criteria: {
      to: state => {
        const publicStates = ['login'];
        const isPrivateState = publicStates.indexOf(state.name) === -1;
        return isPrivateState && isNotLoggedInCostlocker();
      }
    },
    callback: (transition) => {
      transition.router.stateService.target('login', transition.params(), { location: true })
    },
    priority: 10,
  },
  {
    event: 'onSuccess',
    criteria: { to: state => state.data && state.data.title },
    callback: (transition) => {
      const params = transition.params();
      const state = transition.to();
      const stateTitle = state.data.title;
      const getTitle = typeof stateTitle === 'function' ? stateTitle : () => stateTitle;
      document.title = `${getTitle(params)} | Costlocker Webhooks`;
      // rerender to change active state in menu - stateService.go reloads only <UIView>
      appState.cursor(['app']).set('currentState', state.name);
    },
    priority: 10,
  },
];

export const config = (router) => {
  router.urlRouter.otherwise(() => '/');
  router.stateService.defaultErrorHandler(setError);
  redirectToRoute = (route, params, e) => {
    if (e) {
      e.preventDefault();
    }
    router.stateService.go(route, params, { location: true });
  };
  isRouteActive = router.stateService.is;
  hooks.forEach(hook => router.transitionService[hook.event](hook.criteria, hook.callback, { priority: hook.priority }));
}
