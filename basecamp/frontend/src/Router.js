import React from 'react';

import { appState, isNotLoggedInCostlocker, isNotLoggedInBasecamp } from './state';
import { fetchFromApi, pushToApi, loginUrls } from './api';
import Login from './auth/Login';
import Projects from './costlocker/Projects';
import Sync from './costlocker/Sync';
import Accounts from './basecamp/Accounts';
import Events from './basecamp/Events';

export let redirectToRoute;

if (isNotLoggedInCostlocker()) {
  fetchFromApi('/user')
    .then((user) => {
      appState.cursor().update(
        auth => auth
          .setIn(['auth', 'costlocker'], user.costlocker)
          .setIn(['auth', 'basecamp'], user.basecamp)
          .setIn(['auth', 'settings'], user.settings)
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

const loadEvents = () => fetchFromApi('/events').then(events => appState.cursor().set('events', events));

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
      allProjects={appState.cursor(['costlocker', 'projects']).deref()}
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
      basecampUser={appState.cursor(['auth', 'basecamp']).deref()}
      costlockerUser={appState.cursor(['auth', 'costlocker']).deref()}
      users={appState.cursor(['auth', 'settings']).deref().users}
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
        editedProject: props.transition.params().clProject,
        get: (type) => appState.cursor(['sync', type]).deref(),
        set: (type) => (e) => appState.cursor(['sync']).set(
          type,
          e.target.type === 'checkbox' ? e.target.checked : e.target.value
        ),
        submit: (e) => {
          e.preventDefault();
          pushToApi(`/basecamp`, appState.cursor(['sync']).deref())
            .then((r) => redirectToRoute('events'))
            .catch((e) => alert('Synchronization has failed'));
        }
      }}
    />,
    resolve: loadCostlockerProjects.concat([
      {
        token: 'loadUrlParms',
        deps: ['$transition$'],
        resolveFn: ($transition$) => {
          const params = $transition$.params();
          if (params.clProject) {
            const projects = appState.cursor(['costlocker', 'projects']).deref().filter(p => p.id == params.clProject);
            if (projects.length) {
              const editedProject = projects[0];
              const basecampProject = editedProject.basecamps[0];
              appState.cursor(['sync']).update(sync => sync
                .set('mode', 'edit')
                .set('costlockerProject', editedProject.id)
                .set('basecampProject', basecampProject.id)
                .set('account', basecampProject.account.id)
                .set('areTodosEnabled', basecampProject.settings.areTodosEnabled)
                .set('isDeletingTodosEnabled', basecampProject.settings.isDeletingTodosEnabled)
                .set('isRevokeAccessEnabled', basecampProject.settings.isRevokeAccessEnabled)
              )
              return;
            }
          }

          const companySettings = appState.cursor(['auth', 'settings']).deref().sync;
          appState.cursor(['sync']).update(sync => sync
            .set('mode', 'create')
            .set('costlockerProject', '')
            .set('basecampProject', '')
            .set('areTodosEnabled', companySettings.areTodosEnabled)
            .set('isDeletingTodosEnabled', companySettings.isDeletingTodosEnabled)
            .set('isRevokeAccessEnabled', companySettings.isRevokeAccessEnabled)
          );
        }
      }
    ]),
  },
  {
    name: 'events',
    url: '/events',
    component: () => <Events
      events={appState.cursor(['events']).deref()}
      refresh={loadEvents}
    />,
    resolve: [
      {
        token: 'loadEvents',
        resolveFn: loadEvents
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
