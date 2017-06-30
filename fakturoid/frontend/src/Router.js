import React from 'react';

import { appState, isNotLoggedInCostlocker, isNotLoggedInFakturoid } from './state';
import { fetchFromApi, pushToApi, loginUrls } from './api';
import Login from './app/Login';
import Invoice from './app/Invoice';
import InvoiceTutorial from './app/InvoiceTutorial';
import Loading from './ui/Loading';

export let redirectToRoute = (route) => console.log('app is not ready', route);
export let isRouteActive = () => false;
const setError = e => appState.cursor(['app']).set('error', e);

const fetchUser = () =>
  fetchFromApi('/user')
    .then((user) => {
      appState.cursor().update(
        auth => auth
          .setIn(['auth', 'isLoading'], false)
          .setIn(['auth', 'costlocker'], user.costlocker)
          .setIn(['auth', 'fakturoid'], user.fakturoid)
          .setIn(['auth', 'isLoggedInFakturoid'], user.isLoggedInFakturoid)
          .setIn(['app', 'csrfToken'], user.csrfToken)
      );
    })
    .catch(e => console.log('Anonymous user'));

if (isNotLoggedInCostlocker()) {
  fetchUser();
}

export const states = [
  {
    name: 'homepage',
    url: '/?loginError',
    redirectTo: 'login',
  },
  {
    name: 'invoice',
    url: '/invoice?project&invoice',
    component: (props) => {
      if (!props.transition.params().invoice || !props.transition.params().project) {
        return <InvoiceTutorial />;
      }
      const subjects = appState.cursor(['fakturoid', 'subjects']).deref();
      const invoice = appState.cursor(['costlocker', 'invoice']).deref();
      if (!subjects || !invoice) {
        return <Loading title="Loading fakturoid clients, Costlocker invoice" />;
      }
      return <Invoice
        costlockerInvoice={invoice}
        fakturoidSubjects={subjects}
        invoiceCursor={appState.cursor(['invoice'])}
        form={{
          get: (type) => appState.cursor(['invoice', type]).deref(),
          set: (type) => (e) => appState.cursor(['invoice']).set(
            type,
            e.target.type === 'checkbox' ? e.target.checked : e.target.value
          ),
          submit: (e) => {
            e.preventDefault();
            const request = {
              fakturoid: appState.cursor(['invoice']).deref().toJS(),
              costlocker: appState.cursor(['costlocker', 'invoice']).deref(),
            };
            pushToApi('/fakturoid', request)
              .catch(setError)
              .then(response => alert('Invoice was created in Fakturoid, updated in Costlocker'));
          }
        }}
      />
    },
    resolve: [
      {
        token: 'loadFakturoidClients',
        resolveFn: () => {
          if (!appState.cursor(['fakturoid', 'subjects']).deref()) {
            fetchFromApi('/fakturoid')
              .catch(setError)
              .then(projects => appState.cursor(['fakturoid']).set('subjects', projects));
          }
        }
      },
      {
        token: 'loadCostlockerInvoice',
        deps: ['$transition$'],
        resolveFn: ($transition$) => {
          const params = $transition$.params();
          if (params.invoice && params.project) {
            fetchFromApi(`/costlocker?project=${params.project}&invoice=${params.invoice}`)
              .catch(setError)
              .then(invoice => appState.cursor(['costlocker']).set('invoice', invoice));
          }
        }
      }
    ],
  },
  {
    name: 'login',
    url: '/login?loginError',
    component: (props) => <Login
      costlockerAuth={appState.cursor(['auth', 'costlocker']).deref()}
      fakturoidAuth={appState.cursor(['auth', 'fakturoid']).deref()}
      isLoggedInFakturoid={appState.cursor(['auth', 'isLoggedInFakturoid']).deref()}
      loginUrls={loginUrls}
      loginError={props.transition.params().loginError} />,
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
    callback: (transition: any) => {
      transition.router.stateService.target('login', transition.params(), { location: true })
    },
    priority: 10,
  },
  {
    event: 'onBefore',
    criteria: {
      to: state => {
        const fakturoidEndpoints = ['invoice'];
        const isFakturoidState = fakturoidEndpoints.indexOf(state.name) !== -1;
        return isFakturoidState && isNotLoggedInFakturoid();
      }
    },
    callback: (transition: any) => {
      alert('Login in Fakturoid before creating invoicing');
      return transition.router.stateService.target('login', undefined, { location: true });
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
    }
    router.stateService.go(route, params, { location: true });
    // rerender to change active state in menu - stateService.go realoads only <UIView>
    appState.cursor(['app']).set('currentState', route);
  };
  isRouteActive = router.stateService.is;
  hooks.forEach(hook => router.transitionService[hook.event](hook.criteria, hook.callback, { priority: hook.priority }));
}
