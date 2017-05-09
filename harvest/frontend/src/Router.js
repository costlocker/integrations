import React from 'react';
import { UIRouterReact, servicesPlugin, pushStateLocationPlugin } from 'ui-router-react';
import { Visualizer } from 'ui-router-visualizer';

import Login from './auth/Login';
import Projects from './harvest/Projects';
import Project from './harvest/Project';
import { PeopleCosts } from './harvest/PeopleCosts';
import Expenses from './harvest/Expenses';
import { Billing } from './harvest/Billing';
import Summary from './harvest/Summary';
import Results from './harvest/Results';
import WizardLayout from './wizard/WizardLayout';
import Steps from './wizard/Steps';
import { appState, isNotLoggedIn } from './state';
import { pushToApi, fetchFromApi, loginUrl } from './api';

const Router = new UIRouterReact();
const steps = new Steps(Router, [
  'Login',
  'Projects',
  'People costs',
  'Expenses',
  'Billing',
  'Summary',
  'Results',
]);

const handleHarvestLogin = (props) => pushToApi('/harvest', props)
  .then(user => appState.cursor().update(
    (state) => (state
      .setIn(['auth', 'harvest'], user.harvest)
      .setIn(['app', 'harvestError'], null)
    )
  ))
  .catch(() => appState.cursor(['app']).set('harvestError', 'Invalid credentials or domain'));

const loadHarvestData = (type) => ([
  {
    token: `load-${type}`,
    resolveFn: () => {
      if (!appState.cursor(['harvest', type]).deref()) {
        const url = appState.cursor(['harvest', 'selectedProject']).deref().links[type];
        fetchFromApi(url).then(data => appState.cursor(['harvest']).set(type, data));
      }
    }
  }
]);

const buildHarvestProjectStep = (step, type, component) => ({
  name: `wizard.${step}`,
  url: `/${step}`,
  component: (props) => {
    const data = type
      ? appState.cursor(['harvest', type]).deref()
      : appState.cursor(['harvest']).deref().toJS();
    return <Project
      project={appState.cursor(['harvest', 'selectedProject']).deref()}
      data={data}
      detailComponent={component(data)}
      steps={steps} />;
  },
  resolve: type ? loadHarvestData(type) : [],
});

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
      auth={appState.cursor(['auth']).deref().toJS()}
      steps={steps} />,
  },
  {
    name: 'wizard.1',
    url: '/1?clLoginError',
    component: (props) => <Login
      isLoggedIn={!isNotLoggedIn()}
      auth={appState.cursor(['auth']).deref().toJS()}
      handleHarvestLogin={handleHarvestLogin}
      goToNextStep={steps.goToNextStep}
      loginUrl={loginUrl}
      clLoginError={props.transition.params().clLoginError}
      harvestLoginError={appState.cursor(['app', 'harvestError']).deref()} />,
    resolve: [
      {
        token: 'loadUser',
        resolveFn: () => {
          if (isNotLoggedIn()) {
            fetchFromApi('/user')
              .then(user => appState.cursor(['auth']).update(
                auth => auth.set('harvest', user.harvest).set('costlocker', user.costlocker)
              ))
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
        appState.cursor().update(
          state => state
            .setIn(['importResult'], null)
            .updateIn(['harvest'], harvest => harvest
              .set('selectedProject', project)
              .set('peoplecosts', null)
              .set('expenses', null)
              .set('billing', null)));
        steps.goToNextStep();
      };
      return <Projects projects={appState.cursor(['harvest', 'projects']).deref()} goToProject={goTo} />;
    },
    resolve: [
      {
        token: 'loadProjects',
        resolveFn: () => {
          if (appState.cursor(['harvest', 'projects']).deref() === null) {
            fetchFromApi('/harvest')
              .then(projects => appState.cursor(['harvest']).set('projects', projects));
          }
        }
      }
    ]
  },
  buildHarvestProjectStep(3, 'peoplecosts', data => <PeopleCosts peopleCosts={data} />),
  buildHarvestProjectStep(4, 'expenses', data => <Expenses expenses={data} />),
  buildHarvestProjectStep(5, 'billing', data => <Billing billing={data} />),
  buildHarvestProjectStep(6, null, data => <Summary project={data} goToNextStep={steps.goToNextStep} />),
  {
    name: 'wizard.7',
    url: '/7',
    component: () => {
      return <Results importResult={appState.cursor(['importResult']).deref()} />;
    },
    resolve: [
      {
        token: 'importProject',
        resolveFn: () => {
          if (appState.cursor(['importResult']).deref() === null) {
            const project = appState.cursor(['harvest']).deref().toJS();
            delete project.projects;
            pushToApi('/costlocker', project)
              .then((result) => appState.cursor().set('importResult', {hasSucceed:true, projectUrl: result.projectUrl}))
              .catch(
                error =>
                  error.response.json().then(response => appState.cursor().set(
                    'importResult',
                    {hasSucceed: false, projectUrl: null, errors: response.errors}
                  ))
              );
          }
        }
      }
    ],
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
