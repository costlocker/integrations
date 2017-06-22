import React from 'react';

import Login from './auth/Login';
import Projects from './harvest/Projects';
import Project from './harvest/Project';
import { PeopleCosts } from './harvest/PeopleCosts';
import Expenses from './harvest/Expenses';
import Summary from './harvest/Summary';
import Results from './harvest/Results';
import WizardLayout from './wizard/WizardLayout';
import Steps from './wizard/Steps';
import { appState, isNotLoggedIn } from './state';
import { pushToApi, fetchFromApi, loginUrls } from './api';

const steps = new Steps([
  'Login',
  'Projects',
  'Personnel costs',
  'Expenses',
  'Summary',
  'Results',
]);

const loadHarvestData = (type) => ([
  {
    token: `load-${type}`,
    resolveFn: () => {
      if (!appState.cursor(['harvest', type]).deref()) {
        const url = appState.cursor(['harvest', 'selectedProject']).deref().links[type];
        const params = `fixedBudget=${appState.cursor(['harvest', 'fixedBudget']).deref()}`;
        fetchFromApi(`${url}&${params}`).then(data => appState.cursor(['harvest']).set(type, data));
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

export const states = [
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
    url: '/1?clLoginError&harvestLoginError',
    component: (props) => <Login
      isLoggedIn={!isNotLoggedIn()}
      auth={appState.cursor(['auth']).deref().toJS()}
      goToNextStep={steps.goToNextStep}
      loginUrls={loginUrls}
      clLoginError={props.transition.params().clLoginError}
      harvestLoginError={props.transition.params().harvestLoginError} />,
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
      const goTo = (project, fixedBudget) => {
        appState.cursor().update(
          state => state
            .setIn(['importResult'], null)
            .updateIn(['harvest'], harvest => harvest
              .set('selectedProject', project)
              .set('fixedBudget', fixedBudget)
              .set('peoplecosts', null)
              .set('expenses', null)
        ))
        steps.goToNextStep();
      };
      return <Projects projects={appState.cursor(['harvest', 'projects']).deref()} goToProject={goTo} />;
    },
    resolve: [
      {
        token: 'loadProjects',
        resolveFn: () => {
          fetchFromApi('/harvest')
            .then(projects => appState.cursor(['harvest']).set('projects', projects));
        }
      }
    ]
  },
  buildHarvestProjectStep(3, 'peoplecosts', data => <PeopleCosts
    peopleCosts={data}
    project={appState.cursor(['harvest', 'selectedProject']).deref()}
    fixedBudget={appState.cursor(['harvest', 'fixedBudget']).deref()}
  />),
  buildHarvestProjectStep(4, 'expenses', data => <Expenses
    expenses={data}
    currencySymbol={appState.cursor(['harvest', 'selectedProject']).deref().client.currency}
  />),
  buildHarvestProjectStep(5, null, data => <Summary project={data} goToNextStep={steps.goToNextStep} />),
  {
    name: 'wizard.6',
    url: '/6',
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

export const config = (router) => {
  router.urlRouter.otherwise(() => '/');
  steps.redirectToRoute = (route) => router.stateService.go(route, undefined, { location: true });
  hooks.forEach(hook => router.transitionService[hook.event](hook.criteria, hook.callback, { priority: hook.priority }));
}
