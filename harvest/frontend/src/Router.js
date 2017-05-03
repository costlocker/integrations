import React from 'react';
import { UIRouterReact, servicesPlugin, pushStateLocationPlugin } from 'ui-router-react';
import { Visualizer } from 'ui-router-visualizer';
import { Map } from 'immutable';

import LoginForm from './harvest/LoginForm';
import Projects from './harvest/Projects';
import Project from './harvest/Project';
import WizardLayout from './wizard/WizardLayout';
import { appState, isNotLoggedIn } from './state';
import { pushToApi, fetchFromApi } from './api';

const Router = new UIRouterReact();
let currentStep = 1;

let goToStep = (step, e) => {
  if (e) {
    e.preventDefault();
  }
  currentStep = step;
  Router.stateService.go(`wizard.${step}`, undefined, { location: true });
};

const handleHarvestLogin = (props) => pushToApi('/harvest', props)
  .then(user => appState.cursor(['user']).set('harvest', Map(user)))
  .then(() => goToStep(2));

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
    component: () => <WizardLayout
      harvestUser={appState.cursor(['user', 'harvest'])}
      isNotLoggedIn={isNotLoggedIn()}
      currentStep={currentStep}
      goToStep={goToStep} />,
  },
  {
    name: 'wizard.1',
    url: '/1',
    component: () => <LoginForm harvestUser={appState.cursor(['user', 'harvest'])} handleHarvestLogin={handleHarvestLogin} />,
  },
  {
    name: 'wizard.2',
    url: '/2',
    component: () => {
      const goTo = (project) => {
        appState.cursor(['harvest']).update(
          harvest => harvest
            .set('selectedProject', project)
            .set('peopleCosts', null)
        );
        goToStep(3);
      };
      return <Projects projects={appState.cursor(['harvest', 'projects']).deref()} goToProject={goTo} />;
    },
    resolve: [
      {
        token: 'loadProjects',
        resolveFn: () => {
          if (!appState.cursor(['harvest', 'projects']).deref().length) {
            fetchFromApi('/harvest')
              .then(projects => appState.cursor(['harvest']).set('projects', projects));
          }
        }
      }
    ]
  },
  {
    name: 'wizard.3',
    url: '/3',
    component: (props) => {
      return <Project
        project={appState.cursor(['harvest', 'selectedProject']).deref()}
        peopleCosts={appState.cursor(['harvest', 'peopleCosts']).deref()} />;
    },
    resolve: [
      {
        token: 'loadPeopleCosts',
        resolveFn: () => {
          if (!appState.cursor(['harvest', 'peopleCosts']).deref()) {
            fetchFromApi(`/harvest?peoplecosts=${appState.cursor(['harvest', 'selectedProject']).deref().id}`)
              .then(peopleCosts => appState.cursor(['harvest']).set('peopleCosts', peopleCosts));
          }
        }
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
  {
    event: 'onBefore',
    criteria: {
      to: state => {
        const isWizardStep = state.name.substr(0, 7) === 'wizard.';
        if (isWizardStep) {
          const selectedStep = parseInt(state.name.replace('wizard.', ''), 0);
          return selectedStep > currentStep;
        }
        return false;
      }
    },
    callback: (transition: any) =>
      transition.router.stateService.target(`wizard.${currentStep}`, undefined, { location: true }),
    priority: 8,
  },
];

plugins.forEach(plugin => Router.plugin(plugin));
states.forEach(state => Router.stateRegistry.register(state));
hooks.forEach(hook => Router.transitionService[hook.event](hook.criteria, hook.callback, { priority: hook.priority }));
Router.urlRouter.otherwise(() => '/');

export { Router };
