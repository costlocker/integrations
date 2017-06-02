import React from 'react';

import { appState, isNotLoggedIn } from './state';
import { fetchFromApi, loginUrls } from './api';
import Login from './auth/Login';

let redirectToRoute;

if (isNotLoggedIn()) {
  fetchFromApi('/user')
    .then((user) => {
      appState.cursor(['auth']).update(
        auth => auth.set('costlocker', user.costlocker)
      );
      redirectToRoute('homepage');
    })
    .catch(e => console.log('Anonymous user'));
}

export const states = [
  {
    name: 'homepage',
    url: '/?clLoginError',
    redirectTo: 'projects',
  },
  {
    name: 'projects',
    url: '/projects',
    component: () => <h1>Projects</h1>,
  },
  {
    name: 'login',
    url: '/login?clLoginError',
    component: (props) => <Login
      auth={appState.cursor(['auth']).deref().toJS()}
      loginUrls={loginUrls}
      clLoginError={props.transition.params().clLoginError} />,
  },
];

const hooks = [
  {
    event: 'onBefore',
    criteria: {
      to: state => {
        const publicStates = ['login'];
        const isPrivateState = publicStates.indexOf(state.name) === -1;
        return isPrivateState && isNotLoggedIn();
      }
    },
    callback: (transition: any) =>
      transition.router.stateService.target('login', undefined, { location: true }),
    priority: 10,
  },
];

export const config = (router) => {
  router.urlRouter.otherwise(() => '/');
  redirectToRoute = (route) => router.stateService.go(route, undefined, { location: true });
  hooks.forEach(hook => router.transitionService[hook.event](hook.criteria, hook.callback, { priority: hook.priority }));
}
