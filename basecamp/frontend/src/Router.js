import React from 'react';

import { appState, isNotLoggedInCostlocker, isNotLoggedInBasecamp } from './state';
import { fetchFromApi, pushToApi, loginUrls } from './api';
import Login from './auth/Login';
import Projects from './costlocker/Projects';
import Sync from './costlocker/Sync';
import Accounts from './basecamp/Accounts';

export let redirectToRoute;

if (isNotLoggedInCostlocker()) {
  fetchFromApi('/user')
    .then((user) => {
      appState.cursor().update(
        auth => auth
          .setIn(['auth', 'costlocker'], user.costlocker)
          .setIn(['auth', 'basecamp'], user.basecamp)
          .setIn(['sync', 'selectedAccount'], user.basecamp ? user.basecamp.accounts[0].id : null)
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
          .then(projects => appState.cursor()
            .setIn(['costlocker', 'projects'], projects)
            .setIn(['sync', 'costlockerProject'], projects[0].id)
          );
      }
    }
  }
];

appState.on('next-animation-frame', function (newStructure, oldStructure, keyPath) {
  const oldId = oldStructure.getIn(['sync', 'selectedAccount']);
  const accountId = newStructure.getIn(['sync', 'selectedAccount']);
  if (oldId !== accountId) {
    fetchFromApi(`/basecamp?account=${accountId}`)
      .then(projects => appState.cursor().update(
        auth => auth
          .setIn(['basecamp', 'projects'], projects)
          .setIn(['sync', 'basecampProject'], projects[0].id)
      ));
  }
});

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
      basecampAccounts={appState.cursor(['auth', 'basecamp']).deref().accounts}
      selectedBasecampAccount={appState.cursor(['sync', 'selectedAccount']).deref()}
      changeBasecampAccount={(e) => appState.cursor(['sync']).set('selectedAccount', e.target.value)}
      selectedCostlockerProject={appState.cursor(['sync', 'costlockerProject']).deref()}
      changeCostlockerProject={(e) => appState.cursor(['sync']).set('costlockerProject', e.target.value)}
      selectedBasecampProject={appState.cursor(['sync', 'basecampProject']).deref()}
      changeBasecampProject={(e) => appState.cursor(['sync']).set('basecampProject', e.target.value)}
      isBasecampProjectCreated={appState.cursor(['sync', 'isProjectCreated']).deref()}
      changeSyncMode={(e) => appState.cursor(['sync']).set('isProjectCreated', e.target.value === "create")}
      redirectToRoute={redirectToRoute}
    />,
    resolve: loadCostlockerProjects,
  },
  {
    name: 'syncInProgress',
    url: '/sync/in-progress',
    component: (props) => <pre>{JSON.stringify(appState.cursor(['sync']).deref(), null, 2)}</pre>,
    resolve: [
      {
        token: 'submitChange',
        resolveFn: () => {
          pushToApi(`/basecamp`, {
            account: appState.cursor(['sync', 'selectedAccount']).deref(),
            costlocker: appState.cursor(['sync', 'costlockerProject']).deref(),
            basecamp: appState.cursor(['sync', 'basecampProject']).deref(),
          })
          .then(r => appState.cursor(['sync']).set('result', r))
          .catch(e => alert('Synchronization has failed'));
        }
      }
    ],
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
    callback: (transition: any) =>
      transition.router.stateService.target('login', undefined, { location: true }),
    priority: 10,
  },
  {
    event: 'onBefore',
    criteria: {
      to: state => {
        const basecampEndpoints = ['sync'];
        const isBasecampState = basecampEndpoints.indexOf(state.name) !== -1;
        return isBasecampState && isNotLoggedInBasecamp();
      }
    },
    callback: (transition: any) => {
      alert('Login in Basecamp before starting synchronization');
      return transition.router.stateService.target('projects', undefined, { location: true });
    },
    priority: 10,
  },
];

export const config = (router) => {
  router.urlRouter.otherwise(() => '/');
  redirectToRoute = (route) => router.stateService.go(route, undefined, { location: true });
  hooks.forEach(hook => router.transitionService[hook.event](hook.criteria, hook.callback, { priority: hook.priority }));
}
