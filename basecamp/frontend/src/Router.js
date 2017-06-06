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
          .setIn(['auth', 'availableAccounts'], user.availableAccounts)
          .setIn(['sync', 'account'], user.basecamp ? user.basecamp.accounts[0].id : null)
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
  const oldId = oldStructure.getIn(['sync', 'account']);
  const accountId = newStructure.getIn(['sync', 'account']);
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
      availableAccounts={appState.cursor(['auth', 'availableAccounts']).deref()}
      loginUrls={loginUrls} />,
  },
  {
    name: 'sync',
    url: '/sync?account&clProject&bcProject',
    component: (props) => <Sync
      costlockerProjects={appState.cursor(['costlocker', 'projects']).deref()}
      basecampProjects={appState.cursor(['basecamp', 'projects']).deref()}
      basecampAccounts={appState.cursor(['auth', 'basecamp']).deref().accounts}
      syncForm={{
        get: (type) => appState.cursor(['sync', type]).deref(),
        set: (type) => (e) => appState.cursor(['sync']).set(
          type,
          e.target.type === 'checkbox' ? e.target.checked : e.target.value
        ),
        submit: (e) => {
          e.preventDefault();
          redirectToRoute('syncInProgress');
        }
      }}
    />,
    resolve: loadCostlockerProjects.concat([
      {
        token: 'loadUrlParms',
        deps: ['$transition$'],
        resolveFn: ($transition$) => {
          const params = $transition$.params();
          if (params.account && params.clProject && params.bcProject) {
            appState.cursor(['sync']).update(sync => sync
              .set('account', params.account)
              .set('mode', 'add')
              .set('costlockerProject', params.clProject)
              .set('basecampProject', params.bcProject)
            )
          }
        }
      }
    ]),
  },
  {
    name: 'syncInProgress',
    url: '/sync/in-progress',
    component: (props) => <pre>{JSON.stringify(appState.cursor(['sync']).deref(), null, 2)}</pre>,
    resolve: [
      {
        token: 'submitChange',
        resolveFn: () => {
          pushToApi(`/basecamp`, appState.cursor(['sync']).deref())
          .then(r => appState.cursor(['sync']).set('result', r))
          .catch((e) => {
            if (e.status === 404) {
              alert('Project was deleted in Basecamp');
              appState.cursor(['costlocker']).set('projects', null); // reload projects
              redirectToRoute('projects');
            } else {
              alert('Synchronization has failed')
            }
          });
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
  redirectToRoute = (route, params) => router.stateService.go(route, params, { location: true });
  hooks.forEach(hook => router.transitionService[hook.event](hook.criteria, hook.callback, { priority: hook.priority }));
}
