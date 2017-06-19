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
import { SyncSettings } from './app/SyncSettings';

export let redirectToRoute = (route) => console.log('app is not ready', route);
export let isRouteActive = () => false;
const syncSettings = new SyncSettings(appState);

const fetchUser = () =>
  fetchFromApi('/user')
    .then((user) => {
      appState.cursor().update(
        auth => auth
          .setIn(['auth', 'isLoading'], false)
          .setIn(['auth', 'costlocker'], user.costlocker)
          .setIn(['auth', 'basecamp'], user.basecamp)
          .setIn(['auth', 'settings'], user.settings)
          .setIn(['sync', 'account'], user.settings.myAccount)
          .setIn(['companySettings'], Map(user.settings.sync))
      );
    })
    .catch(e => console.log('Anonymous user'));

if (isNotLoggedInCostlocker()) {
  fetchUser();
}

const fetchProjects = () =>
   fetchFromApi('/costlocker')
    .then(projects => {
      appState.cursor(['costlocker']).set('projects', projects);
      return projects;
    });

const loadCostlockerProjects = (callback) => [
  {
    token: 'loadCostlockerProjects',
    resolveFn: () => {
      if (!appState.cursor(['costlocker', 'projects']).deref()) {
        fetchProjects().then(callback ? callback : (r => r));
      }
    }
  }
];

const loadEvents = (clProject) =>
  fetchFromApi(clProject ? `/events?project=${clProject}` : '/events')
  .then(events => appState.cursor().set('events', events));

appState.on('next-animation-frame', function (newStructure, oldStructure, keyPath) {
  const oldId = oldStructure.getIn(['sync', 'account']);
  const accountId = newStructure.getIn(['sync', 'account']);
  if (oldId !== accountId && accountId) {
    fetchFromApi(`/basecamp?account=${accountId}`)
      .then(data => appState.cursor(['basecamp']).update(
        bc => bc.set('projects', data.projects).set('companies', data.companies)
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
    resolve: loadCostlockerProjects(),
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
      accounts={appState.cursor(['auth', 'settings']).deref().accounts.basecamp}
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
    url: '/sync?account&clProject',
    component: (props) => <Sync
      costlockerProjects={appState.cursor(['costlocker', 'projects']).deref()}
      basecampProjects={appState.cursor(['basecamp', 'projects']).deref()}
      basecampCompanies={appState.cursor(['basecamp', 'companies']).deref()}
      basecampAccounts={appState.cursor(['auth', 'settings']).deref().accounts.basecamp}
      syncForm={{
        editedProject: props.transition.params().clProject,
        get: (type) => appState.cursor(['sync', type]).deref(),
        set: (type) => (e) => appState.cursor(['sync']).set(
          type,
          e.target.type === 'checkbox' ? e.target.checked : e.target.value
        ),
        updateCostlockerProjects: (updater) => appState.cursor(['sync', 'costlockerProject']).update(updater),
        submit: (e) => {
          e.preventDefault();
          pushToApi(`/sync`, appState.cursor(['sync']).deref())
            .then((r) => redirectToRoute('events'))
            .catch((e) => alert('Synchronization has failed'));
        }
      }}
    />,
    resolve:
      loadCostlockerProjects(
        projects => syncSettings.loadProjectSettings(projects)
      ).concat([
      {
        token: 'loadUrlParms',
        deps: ['$transition$'],
        resolveFn: ($transition$) => {
          const params = $transition$.params();
          if (params.clProject) {
            syncSettings.setProjectId(params.clProject);
            syncSettings.loadProjectSettings(
              appState.cursor(['costlocker', 'projects']).deref()
            );
            return;
          }
          syncSettings.loadCompanySettings();
        }
      }
    ]),
  },
  {
    name: 'settings',
    url: '/settings',
    component: (props) => <Settings
      accounts={appState.cursor(['auth', 'settings']).deref().accounts}
      form={{
        get: (type) => appState.cursor(['companySettings', type]).deref(),
        set: (type) => (e) => appState.cursor(['companySettings']).set(
          type,
          e.target.type === 'checkbox' ? e.target.checked : e.target.value
        ),
        submit: (e) => {
          e.preventDefault();
          pushToApi(`/settings`, appState.cursor(['companySettings']).deref())
            .catch((e) => alert('Save has failed'));
        }
      }}
    />,
  },
  {
    name: 'events',
    url: '/events?clProject',
    component: (props) => <Events
      events={appState.cursor(['events']).deref()}
      refresh={() => loadEvents(props.transition.params().clProject).then(fetchProjects())} // hotfix for reloading projects list
    />,
    resolve: loadCostlockerProjects().concat([
      {
        token: 'loadEvents',
        deps: ['$transition$'],
        resolveFn: ($transition$) => {
          const params = $transition$.params();
          loadEvents(params.clProject);
          return true;
        },
      }
    ]),
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
