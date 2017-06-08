import React from 'react';
import { Map } from 'immutable';

import { appState, isNotLoggedInCostlocker, isNotLoggedInBasecamp } from './state';
import { fetchFromApi, pushToApi, loginUrls } from './api';
import Login from './app/Login';
import Projects from './app/Projects';
import Sync from './app/Sync';
import Accounts from './app/Accounts';
import Settings from './app/Settings';
import Events from './app/Events';

export let redirectToRoute = (route) => console.log('app is not ready', route);
export let isRouteActive = () => false;

const fetchUser = () =>
  fetchFromApi('/user')
    .then((user) => {
      appState.cursor().update(
        auth => auth
          .setIn(['auth', 'isLoading'], false)
          .setIn(['auth', 'costlocker'], user.costlocker)
          .setIn(['auth', 'basecamp'], user.basecamp)
          .setIn(['auth', 'settings'], user.settings)
          .setIn(['sync', 'account'], user.basecamp ? user.basecamp.accounts[0].id : null)
          .setIn(['companySettings'], user.settings ? Map(user.settings.sync) : null)
      );
    })
    .catch(e => console.log('Anonymous user'));

if (isNotLoggedInCostlocker()) {
  fetchUser();
}

const fetchProjects = () =>
   fetchFromApi('/costlocker')
    .then(projects => appState.cursor()
      .setIn(['costlocker', 'projects'], projects)
      .setIn(['sync', 'costlockerProject'], projects[0].id)
    );

const loadCostlockerProjects = [
  {
    token: 'loadCostlockerProjects',
    resolveFn: () => {
      if (!appState.cursor(['costlocker', 'projects']).deref()) {
        fetchProjects();
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
    url: '/?loginError',
    redirectTo: 'projects',
  },
  {
    name: 'projects',
    url: '/projects',
    component: () => <Projects
      allProjects={appState.cursor(['costlocker', 'projects']).deref()}
      disconnect={(id) =>
        pushToApi(`/disconnect`, { project: id })
          .then(() => fetchProjects())
          .catch((e) => alert('Disconnect has failed'))
      }
    />,
    resolve: loadCostlockerProjects,
  },
  {
    name: 'login',
    url: '/login?loginError',
    component: (props) => <Login
      costlockerAuth={appState.cursor(['auth', 'costlocker']).deref()}
      loginUrls={loginUrls}
      loginError={props.transition.params().loginError} />,
  },
  {
    name: 'accounts',
    url: '/accounts?loginError',
    component: (props) => <Accounts
      basecampUser={appState.cursor(['auth', 'basecamp']).deref()}
      costlockerUser={appState.cursor(['auth', 'costlocker']).deref()}
      users={appState.cursor(['auth', 'settings']).deref().users}
      loginError={props.transition.params().loginError}
      disconnect={(id) =>
        pushToApi(`/disconnect`, { user: id })
          .then(() => fetchUser())
          .catch((e) => alert('Disconnect has failed'))
      }
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

          const companySettings = appState.cursor(['companySettings']).deref();
          appState.cursor(['sync']).update(sync => sync
            .set('mode', 'create')
            .set('costlockerProject', '')
            .set('basecampProject', '')
            .set('areTodosEnabled', companySettings.get('areTodosEnabled'))
            .set('isDeletingTodosEnabled', companySettings.get('isDeletingTodosEnabled'))
            .set('isRevokeAccessEnabled', companySettings.get('isRevokeAccessEnabled'))
          );
        }
      }
    ]),
  },
  {
    name: 'settings',
    url: '/settings',
    component: (props) => <Settings
      form={{
        get: (type) => appState.cursor(['companySettings', type]).deref(),
        set: (type) => (e) => appState.cursor(['companySettings']).set(
          type,
          e.target.type === 'checkbox' ? e.target.checked : e.target.value
        ),
        submit: (e) => {
          e.preventDefault();
          pushToApi(`/settings`, appState.cursor(['companySettings']).deref())
            .then((r) => appState.cursor(['companySettings']).set('isCostlockerWebhookEnabled', true))
            .catch((e) => alert('Save has failed'));
        }
      }}
    />,
  },
  {
    name: 'events',
    url: '/events',
    component: () => <Events
      events={appState.cursor(['events']).deref()}
      refresh={() => loadEvents().then(fetchProjects())} // hotfix for reloading projects list
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
      transition.router.stateService.target('login', transition.params(), { location: true }),
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
  redirectToRoute = (route, params, e) => {
    if (e) {
      e.preventDefault();
    }
    router.stateService.go(route, params, { location: true });
    // rerender to change active state in menu - stateService.go realoads only <UIView>
    appState.cursor(['app']).set('currentState', route);
  };
  isRouteActive = router.stateService.is;
  hooks.forEach(hook => router.transitionService[hook.event](hook.criteria, hook.callback, { priority: hook.priority }));
}
