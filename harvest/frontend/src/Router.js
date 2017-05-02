import React from 'react';
import { UIRouterReact, servicesPlugin, pushStateLocationPlugin } from 'ui-router-react';
import { Visualizer } from 'ui-router-visualizer';
import { Map } from 'immutable';

import LoginForm from './harvest/LoginForm';
import WizardLayout from './wizard/WizardLayout';
import { appState, isNotLoggedIn } from './state';
import { pushToApi, fetchFromApi } from './api';

const Router = new UIRouterReact();

const handleHarvestLogin = (props) => pushToApi('/harvest', props)
  .then(user => appState.cursor(['user']).set('harvest', Map(user)))
  .then(() => Router.stateService.go('wizard.2', undefined, { location: true }));

const states = [
  {
    name: 'homepage',
    url: '/',
    redirectTo: 'wizard.1',
  },
  {
    name: 'wizard',
    url: '/step',
    redirectTo: 'wizard.1',
    component: () => <WizardLayout harvestUser={appState.cursor(['user', 'harvest'])} isNotLoggedIn={isNotLoggedIn()} />,
  },
  {
    name: 'wizard.1',
    url: '/1',
    component: () => <LoginForm harvestUser={appState.cursor(['user', 'harvest'])} handleHarvestLogin={handleHarvestLogin} />,
  },
  {
    name: 'wizard.2',
    url: '/2',
    component: ({ resolves }) => {
      return <pre>{JSON.stringify(resolves.projects, null, 2)}</pre>;
    },
    resolve: [
      {
        token: 'projects',
        resolveFn: () => fetchFromApi('/harvest')
      }
    ]
  },
];

let plugins = [servicesPlugin, pushStateLocationPlugin, Visualizer];

console.log(appState.cursor(['user', 'harvest', 'company_name']).deref());

const hooks = [
  {
    event: 'onBefore',
    criteria: {
      to: state => {
        const publicStates = ['homepage', 'wizard', 'wizard.1'];
        const isPrivateState = publicStates.indexOf(state.name) === -1;
        return isPrivateState && isNotLoggedIn();
      }
    },
    callback: (transition: any) =>
      transition.router.stateService.target('wizard', undefined, { location: true }),
    priority: 10,
  },
];

plugins.forEach(plugin => Router.plugin(plugin));
states.forEach(state => Router.stateRegistry.register(state));
hooks.forEach(hook => Router.transitionService[hook.event](hook.criteria, hook.callback, { priority: hook.priority }));
Router.urlRouter.otherwise(() => '/');

export { Router };
