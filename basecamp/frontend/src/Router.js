import React from 'react';

import { appState, isNotLoggedIn } from './state';
import { fetchFromApi, loginUrls } from './api';
import Login from './auth/Login';
import Projects from './costlocker/Projects';
import Sync from './costlocker/Sync';
import Accounts from './basecamp/Accounts';

export let redirectToRoute;

if (isNotLoggedIn()) {
  fetchFromApi('/user')
    .then((user) => {
      appState.cursor(['auth']).update(
        auth => auth.set('costlocker', user.costlocker).set('basecamp', user.basecamp)
      );
      redirectToRoute('homepage');
    })
    .catch(e => console.log('Anonymous user'));
}

const loadCostlockerProjects = [
  {
    token: 'loadCostlockerProjects',
    resolveFn: () => {
      if (!appState.cursor(['costlocker', 'projects']).deref()) {
        fetchFromApi('/costlocker')
          .then(projects => appState.cursor(['costlocker']).set('projects', projects));
      }
    }
  }
];

const loadBasecampProjects = [
  {
    token: 'loadBasecampProjects',
    resolveFn: () => {
      if (!appState.cursor(['basecamp', 'projects']).deref()) {
        fetchFromApi('/basecamp')
          .then(projects => appState.cursor(['basecamp']).set('projects', projects));
      }
    }
  }
];

export const states = [
  {
    name: 'homepage',
    url: '/?clLoginError',
    redirectTo: 'projects',
  },
  {
    name: 'projects',
    url: '/projects',
    component: () => <Projects
      projects={appState.cursor(['costlocker', 'projects']).deref()}
      redirectToRoute={redirectToRoute} />,
    resolve: loadCostlockerProjects,
  },
  {
    name: 'login',
    url: '/login?clLoginError',
    component: (props) => <Login
      auth={appState.cursor(['auth']).deref().toJS()}
      loginUrls={loginUrls}
      clLoginError={props.transition.params().clLoginError} />,
  },
  {
    name: 'basecamp',
    url: '/basecamp',
    component: (props) => <Accounts
      basecamp={appState.cursor(['auth', 'basecamp']).deref()}
      loginUrls={loginUrls} />,
  },
  {
    name: 'sync',
    url: '/sync',
    component: (props) => <Sync
      costlockerProjects={appState.cursor(['costlocker', 'projects']).deref()}
      basecampProjects={appState.cursor(['basecamp', 'projects']).deref()}
    />,
    resolve: loadCostlockerProjects.concat(loadBasecampProjects),
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
