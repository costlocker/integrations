import React from 'react';

import { appState, isNotLoggedInCostlocker, isNotLoggedInFakturoid } from './state';
import { fetchFromApi, pushToApi, loginUrls } from './api';
import Login from './app/Login';
import Invoice from './app/Invoice';
import InvoiceLines from './app/InvoiceLines'
import InvoiceTutorial from './app/InvoiceTutorial';
import NewSubject from './app/NewSubject';
import Loading from './ui/Loading';

export let redirectToRoute = (route) => console.log('app is not ready', route);
export let isRouteActive = () => false;
const setError = e => appState.cursor(['app']).set('error', e);

const fetchUser = (queryString) =>
  fetchFromApi(`/user${queryString}`)
    .catch(setError)
    .then((user) => {
      appState.cursor().update(
        auth => auth
          .setIn(['auth', 'isLoading'], false)
          .setIn(['auth', 'costlocker'], user.costlocker)
          .setIn(['auth', 'fakturoid'], user.fakturoid)
          .setIn(['fakturoid', 'slug'], user.fakturoid ? user.fakturoid.account.slug : '')
          .setIn(['auth', 'isLoggedInFakturoid'], user.isLoggedInFakturoid)
          .setIn(['app', 'csrfToken'], user.csrfToken)
          .setIn(['app', 'isDisabled'], user.isAddonDisabled)
      );
    });

if (isNotLoggedInCostlocker()) {
  fetchUser(window.location.search);
}

const fetchLatestInvoices = () =>
  fetchFromApi(`/costlocker`)
    .catch(setError)
    .then(invoices => appState.cursor(['costlocker']).set('latestInvoices', invoices));

const fetchProjectInvoices = (project) =>
  fetchFromApi(`/costlocker?project=${project}`)
    .catch(setError)
    .then(invoices => appState.cursor(['costlocker']).set('projectInvoices', invoices));

const fetchInvoice = ({ project, billing }) => {
  fetchProjectInvoices(project);
  return fetchFromApi(`/costlocker?project=${project}&billing=${billing}&query=billing`)
    .catch(setError)
    .then(invoice => appState.cursor()
      .setIn(['costlocker', 'invoice'], invoice)
      .setIn(['invoice', 'subject'], invoice.fakturoid ? invoice.fakturoid.template.subject : null)
    );
};

const fetchSubjects = () =>
  fetchFromApi('/fakturoid')
    .catch(setError)
    .then(projects => appState.cursor(['fakturoid']).set('subjects', projects));

export const states = [
  {
    name: 'homepage',
    url: '/?loginError',
    redirectTo: 'login',
  },
  {
    name: 'invoice',
    url: '/invoice?project&billing',
    data: {
      title: (params) => {
        if (!params.billing || !params.project) {
          return 'Invoices';
        }
        return 'Create invoice';
      },
    },
    component: (props) => {
      const params = props.transition.params();
      const subjects = appState.cursor(['fakturoid', 'subjects']).deref();
      if (!params.billing || !params.project) {
        return <InvoiceTutorial
          latestInvoices={appState.cursor(['costlocker', 'latestInvoices']).deref()}
          subjects={subjects}
        />;
      }
      const invoice = appState.cursor(['costlocker', 'invoice']).deref();
      if (!subjects || !invoice) {
        return <Loading title="Loading fakturoid clients, Costlocker invoice" />;
      }
      if (appState.cursor(['app', 'isSendingForm']).deref()) {
        return <Loading title="Creating invoice in Fakturoid" />;
      }
      return <Invoice
        invoice={invoice}
        fakturoidSubjects={subjects}
        projectInvoices={appState.cursor(['costlocker', 'projectInvoices']).deref()}
        lines={new InvoiceLines(appState.cursor(['invoice', 'lines']))}
        forceUpdate={() => appState.cursor(['invoice']).set('isForced', true)}
        form={{
          get: (type) => appState.cursor(['invoice', type]).deref(),
          set: (type) => (e) => appState.cursor(['invoice']).set(
            type,
            e.target.type === 'checkbox' ? e.target.checked : e.target.value
          ),
          submit: (e) => {
            e.preventDefault();
            const request = {
              fakturoid: appState.cursor(['invoice']).deref().toJS(),
              costlocker: appState.cursor(['costlocker', 'invoice']).deref().costlocker,
            };
            appState.cursor(['app']).set('isSendingForm', true);
            pushToApi('/fakturoid?action=createInvoice', request)
              .catch(error => {
                appState.cursor(['app']).set('isSendingForm', false);
                setError(error);
              })
              .then(() =>
                fetchInvoice(params).then(
                  appState.cursor().update(
                    app => app
                      .setIn(['app', 'isSendingForm'], false)
                      .setIn(['invoice', 'isForced'], false)
                  )
                )
              );
          }
        }}
        reloadSubjects={(e) => {
          e.preventDefault();
          pushToApi('/fakturoid?action=downloadSubjects', {})
            .catch(setError)
            .then(() => fetchSubjects().then(() => alert('Customers reloaded')))
        }}
      />
    },
    resolve: [
      {
        token: 'loadFakturoidClients',
        resolveFn: () => {
          if (!appState.cursor(['fakturoid', 'subjects']).deref()) {
            fetchSubjects();
          }
        }
      },
      {
        token: 'loadInvoice',
        deps: ['$transition$'],
        resolveFn: ($transition$) => {
          const params = $transition$.params();
          if (params.billing && params.project) {
            fetchInvoice(params);
          } else {
            fetchLatestInvoices();
          }
        }
      }
    ],
  },
  {
    name: 'createSubject',
    url: '/customer',
    data: {
      title: 'Create customer'
    },
    component: () => <NewSubject
      form={{
        get: (type) => appState.cursor(['subject', type]).deref(),
        set: (type) => (e) => appState.cursor(['subject']).set(
          type,
          e.target.type === 'checkbox' ? e.target.checked : e.target.value
        ),
        submit: (e) => {
          e.preventDefault();
          const request = appState.cursor(['subject']).deref().toJS();
          const costlocker = appState.cursor(['costlocker', 'invoice']).deref().costlocker;
          let params = {};
          if (costlocker) {
            params = {
              project: costlocker.project.id,
              billing: costlocker.billing.item.billing_id,
            };
          }
          pushToApi('/fakturoid?action=createSubject', request)
            .catch(setError)
            .then((response) => {
              appState.cursor().update(app => app.setIn(['fakturoid', 'subjects'], null));
              redirectToRoute('invoice', params);
            });
        }
      }}
    />,
  },
  {
    name: 'login',
    url: '/login?loginError',
    data: {
      title: 'Login'
    },
    component: (props) => <Login
      costlockerAuth={appState.cursor(['auth', 'costlocker']).deref()}
      fakturoidAuth={appState.cursor(['auth', 'fakturoid']).deref()}
      isLoggedInFakturoid={appState.cursor(['auth', 'isLoggedInFakturoid']).deref()}
      loginUrls={loginUrls}
      loginError={props.transition.params().loginError}
      form={{
        get: (type) => appState.cursor(['fakturoid', type]).deref(),
        set: (type) => (e) => appState.cursor(['fakturoid']).set(
          type,
          e.target.type === 'checkbox' ? e.target.checked : e.target.value
        ),
      }} />,
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
    callback: (transition) => {
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
    callback: (transition) => {
      if (!isNotLoggedInCostlocker()) {
        alert('Login in Fakturoid before creating invoicing');
      }
      return transition.router.stateService.target('login', undefined, { location: true });
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
      document.title = `${getTitle(params)} | Costlocker → Fakturoid`;
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
    }
    router.stateService.go(route, params, { location: true });
  };
  isRouteActive = router.stateService.is;
  hooks.forEach(hook => router.transitionService[hook.event](hook.criteria, hook.callback, { priority: hook.priority }));
}
