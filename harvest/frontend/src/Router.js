import React from 'react';
import { UIRouterReact, servicesPlugin, pushStateLocationPlugin } from 'ui-router-react';
import { Visualizer } from 'ui-router-visualizer';

import LoginForm from './harvest/LoginForm';
import Projects from './harvest/Projects';
import Project from './harvest/Project';
import PeopleCosts from './harvest/PeopleCosts';
import Expenses from './harvest/Expenses';
import Billing from './harvest/Billing';
import User from './harvest/User';
import WizardLayout from './wizard/WizardLayout';
import Steps from './wizard/Steps';
import { appState, isNotLoggedIn } from './state';
import { pushToApi, fetchFromApi } from './api';

const Router = new UIRouterReact();
const steps = new Steps(Router, [
  'Login',
  'Projects',
  'People costs',
  'Expenses',
  'Billing',
  'Summary',
]);

const handleHarvestLogin = (props) => pushToApi('/harvest', props)
  .then(user => appState.cursor(['harvest']).set('user', user))
  .then(() => steps.goToNextStep())
  .catch(() => alert('Invalid credentials'));

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
      user={isNotLoggedIn() ? <em>Not logged in</em> : <User harvestUser={appState.cursor(['harvest', 'user']).deref()} />}
      steps={steps} />,
  },
  {
    name: 'wizard.1',
    url: '/1',
    component: () => <LoginForm
      harvestUser={appState.cursor(['harvest', 'user']).deref()}
      handleHarvestLogin={handleHarvestLogin}
      goToNextStep={steps.goToNextStep} />,
    resolve: [
      {
        token: 'loadUser',
        resolveFn: () => {
          if (isNotLoggedIn()) {
            fetchFromApi('/user')
              .then(user => appState.cursor(['harvest']).set('user', user))
              .catch(e => console.log('Anonymous user'));
          }
        }
      }
    ]
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
            .set('expenses', null)
            .set('billing', null)
        );
        steps.goToNextStep();
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
      const data = appState.cursor(['harvest', 'peopleCosts']).deref();
      return <Project
        project={appState.cursor(['harvest', 'selectedProject']).deref()}
        data={data}
        detailComponent={<PeopleCosts peopleCosts={data} />}
        steps={steps} />;
    },
    resolve: [
      {
        token: 'loadPeopleCosts',
        resolveFn: () => {
          if (!appState.cursor(['harvest', 'peopleCosts']).deref()) {
            const url = appState.cursor(['harvest', 'selectedProject']).deref().links.peoplecosts;
            fetchFromApi(url).then(data => appState.cursor(['harvest']).set('peopleCosts', data));
          }
        }
      }
    ]
  },
  {
    name: 'wizard.4',
    url: '/4',
    component: (props) => {
      const data = appState.cursor(['harvest', 'expenses']).deref();
      return <Project
        project={appState.cursor(['harvest', 'selectedProject']).deref()}
        data={data}
        detailComponent={<Expenses expenses={data} />}
        steps={steps} />;
    },
    resolve: [
      {
        token: 'loadExpenses',
        resolveFn: () => {
          if (!appState.cursor(['harvest', 'expenses']).deref()) {
            const url = appState.cursor(['harvest', 'selectedProject']).deref().links.expenses;
            fetchFromApi(url).then(data => appState.cursor(['harvest']).set('expenses', data));
          }
        }
      }
    ]
  },
  {
    name: 'wizard.5',
    url: '/5',
    component: (props) => {
      const data = appState.cursor(['harvest', 'billing']).deref();
      return <Project
        project={appState.cursor(['harvest', 'selectedProject']).deref()}
        data={data}
        detailComponent={<Billing billing={data} />}
        steps={steps} />;
    },
    resolve: [
      {
        token: 'loadBilling',
        resolveFn: () => {
          if (!appState.cursor(['harvest', 'billing']).deref()) {
            const url = appState.cursor(['harvest', 'selectedProject']).deref().links.billing;
            fetchFromApi(url).then(data => appState.cursor(['harvest']).set('billing', data));
          }
        }
      }
    ]
  },
  {
    name: 'wizard.6',
    url: '/6',
    component: (props) => {
      return <Project
        project={appState.cursor(['harvest', 'selectedProject']).deref()}
        data={[]}
        detailComponent={<div>Import summary...</div>}
        steps={steps} />;
    },
  },
];

let plugins = [servicesPlugin, pushStateLocationPlugin, Visualizer];

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
          return steps.isInvalidStep(selectedStep);
        }
        return false;
      }
    },
    callback: (transition: any) =>
      transition.router.stateService.target(`wizard.${steps.getCurrentStep()}`, undefined, { location: true }),
    priority: 8,
  },
];

plugins.forEach(plugin => Router.plugin(plugin));
states.forEach(state => Router.stateRegistry.register(state));
hooks.forEach(hook => Router.transitionService[hook.event](hook.criteria, hook.callback, { priority: hook.priority }));
Router.urlRouter.otherwise(() => '/');

export { Router };
