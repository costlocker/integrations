import React from 'react';
import moment from 'moment';
import { Map } from 'immutable';

import { appState, isNotLoggedInCostlocker, isNotLoggedInBasecamp } from './state';
import { fetchFromApi, pushToApi, loginUrls } from './api';
import { serverTimezoneOffsetInHours } from './config';
import Form from './app/Form';
import Login from './app/Login';
import Projects from './app/Projects';
import Sync from './app/Sync';
import Accounts from './app/Accounts';
import Settings from './app/Settings';
import Events from './app/Events';
import Help from './app/Help';
import { SyncSettings } from './app/SyncSettings';

export let redirectToRoute = (route) => console.log('app is not ready', route);
export let generateUrl = () => '';
export let isRouteActive = () => false;
const syncSettings = new SyncSettings(appState);
const setError = e => appState.cursor(['app']).set('error', e);

const fetchUser = () =>
  fetchFromApi('/user')
    .then((user) => {
      appState.cursor().update(
        auth => auth
          .setIn(['auth', 'isLoading'], false)
          .setIn(['auth', 'costlocker'], user.costlocker)
          .setIn(['auth', 'basecamp'], user.basecamp)
          .setIn(['auth', 'settings'], user.settings)
          .setIn(['app', 'csrfToken'], user.csrfToken)
          .setIn(['sync', 'account'], user.settings.myAccount)
          .setIn(['companySettings'], Map(user.settings.sync))
          .setIn(['app', 'isDisabled'], user.isAddonDisabled)
      );
    })
    .catch(e => console.log('Anonymous user'));

if (isNotLoggedInCostlocker()) {
  fetchUser();
}

const fetchProjects = (customFilter) => {
  const filter = customFilter ? customFilter : appState.cursor(['costlocker', 'state']).deref();
  return fetchFromApi(`/costlocker?state=${filter}`)
    .catch(setError)
    .then(projects => {
      appState.cursor(['costlocker']).update(
        state => state
          .set('projects', projects)
          .set('state', filter)
          .set('isSearching', false)
      );
      return projects;
    });
}

const loadRunningProjects = (callback) => [
  {
    token: 'loadCostlockerProjects',
    resolveFn: () => {
      if (
        !appState.cursor(['costlocker', 'projects']).deref() ||
        appState.cursor(['costlocker', 'state']).deref() !== 'running'
      ) {
        fetchProjects('running').then(callback ? callback : (r => r));
      }
    }
  }
];

const loadEvents = (clProject, updater) =>
  fetchFromApi(clProject ? `/events?project=${clProject}` : '/events')
    .catch(setError)
    .then(events => appState.cursor().update(app => {
      const updated = updater ? updater(app) : app;
      return updated.set('events', events);
    }));

const disconnectBasecamp = (params, onSuccess) => {
  if (isNotLoggedInBasecamp()) {
    alert('Login to Basecamp before disconnect');
    return;
  }
  return pushToApi(`/disconnect`, params)
      .then(onSuccess)
      .catch((e) => alert('Disconnect has failed'));
};

appState.on('next-animation-frame', function (newStructure, oldStructure, keyPath) {
  const oldId = oldStructure.getIn(['sync', 'account']);
  const accountId = newStructure.getIn(['sync', 'account']);
  if (oldId !== accountId && accountId && !isNotLoggedInCostlocker()) {
    fetchFromApi(`/basecamp?account=${accountId}`)
      .catch(setError)
      .then(data => appState.cursor(['basecamp']).update(
        bc => bc
          .set('isAccountAvailable', data.isAvailable)
          .set('projects', data.projects)
          .set('companies', data.companies)
      ));
  }

  const isSearchUpdated = (field, keyPath) =>
    keyPath.length === 1 &&
    keyPath[0] === 'costlocker' &&
    newStructure.getIn(['costlocker', field]) !== oldStructure.getIn(['costlocker', field]);

  if (isSearchUpdated('state', keyPath)) {
    fetchProjects(newStructure.getIn(['costlocker', 'state']));
  }
});

export const states = [
  {
    name: 'homepage',
    url: '/?loginError',
    redirectTo: 'projects',
  },
  {
    name: 'help',
    url: '/help',
    data: {
      title: 'Help',
    },
    component: () => <Help />,
  },
  {
    name: 'projects',
    url: '/projects',
    data: {
      title: 'Projects',
    },
    component: () => <Projects
      allProjects={appState.cursor(['costlocker', 'projects']).deref()}
      disconnect={(id) => disconnectBasecamp({ project: id }, fetchProjects)}
      form={new Form({
        stateKey: 'costlocker',
        alwaysSet: (s) => s.setIn(['isSearching'], true),
      })}
    />,
    resolve: loadRunningProjects(),
  },
  {
    name: 'login',
    url: '/login?loginError',
    data: {
      title: 'Login',
    },
    component: (props) => <Login
      costlockerAuth={appState.cursor(['auth', 'costlocker']).deref()}
      loginUrls={loginUrls}
      loginError={props.transition.params().loginError} />,
  },
  {
    name: 'accounts',
    url: '/accounts?loginError',
    data: {
      title: 'Accounts',
    },
    component: (props) => <Accounts
      basecampUser={appState.cursor(['auth', 'basecamp']).deref()}
      costlockerUser={appState.cursor(['auth', 'costlocker']).deref()}
      accounts={appState.cursor(['auth', 'settings']).deref().accounts.basecamp}
      loginError={props.transition.params().loginError}
      disconnect={(id) => disconnectBasecamp({ user: id }, fetchUser)}
      loginUrls={loginUrls} />,
  },
  {
    name: 'sync',
    url: '/sync?account&clProject',
    data: {
      title: () => syncSettings.isExistingProjectEdited ? 'Refresh project' : 'Add project',
    },
    component: () => <Sync
      costlockerProjects={appState.cursor(['costlocker', 'projects']).deref()}
      basecamp={appState.cursor(['basecamp']).deref()}
      basecampAccounts={appState.cursor(['auth', 'settings']).deref().accounts.basecamp}
      syncForm={new Form({
        stateKey: 'sync',
        submit: () =>
          pushToApi(`/sync`, appState.cursor(['sync']).deref())
            .then((r) => redirectToRoute('events'))
            .catch((e) => alert('Synchronization has failed'))
      })}
      isExistingProjectEdited={syncSettings.isExistingProjectEdited}
    />,
    resolve:
      loadRunningProjects(
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
    data: {
      title: 'Settings',
    },
    component: (props) => <Settings
      accounts={appState.cursor(['auth', 'settings']).deref().accounts}
      form={new Form({
        stateKey: 'companySettings',
        submit: () =>
          pushToApi(`/settings`, appState.cursor(['companySettings']).deref())
            .then(() => alert('Settings saved'))
            .catch((e) => alert('Save has failed'))
      })}
    />,
  },
  {
    name: 'events',
    url: '/events?clProject',
    data: {
      title: params => params.clProject ? 'Project events' : 'Events',
    },
    component: (props) => {
      const loadEventsAndResetProjects = () => loadEvents(
        props.transition.params().clProject,
        app => app.setIn(['costlocker', 'projects'], null) // reset projects so new projects are detected
      );
      return <Events
        events={appState.cursor(['events']).deref()}
        refresh={loadEventsAndResetProjects}
        undo={(link) => () => {
          pushToApi(link, {})
            .then(loadEventsAndResetProjects)
            .catch((e) => alert('Undo has failed'))
        }}
        dateToMoment={date => moment(`${date} +0${serverTimezoneOffsetInHours}00`, "YYYY-MM-DD HH:mm Z")}
      />;
    },
    resolve: [
      {
        token: 'loadEvents',
        deps: ['$transition$'],
        resolveFn: ($transition$) => {
          const params = $transition$.params();
          loadEvents(params.clProject);
          return true;
        },
      }
    ],
  },
];

const hooks = [
  {
    event: 'onBefore',
    criteria: {
      to: state => {
        const publicStates = ['login', 'help'];
        const isPrivateState = publicStates.indexOf(state.name) === -1;
        return isPrivateState && isNotLoggedInCostlocker();
      }
    },
    callback: (transition: any) => {
      transition.abort();
      redirectToRoute('login', transition.params());
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
      document.title = `${getTitle(params)} | Costlocker ↔ Basecamp`;
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
      if (e.metaKey || e.ctrlKey) {
        const absoluteUrl = router.stateService.href(route, params, { absolute: true })
          .replace(':3000:3000', ':3000'); // hotfix https://github.com/ui-router/core/issues/70
        window.open(absoluteUrl, '_blank');
        return;
      }
    }
    router.stateService.go(route, params, { location: true });
  };
  generateUrl = router.stateService.href;
  isRouteActive = router.stateService.is;
  hooks.forEach(hook => router.transitionService[hook.event](hook.criteria, hook.callback, { priority: hook.priority }));
}
