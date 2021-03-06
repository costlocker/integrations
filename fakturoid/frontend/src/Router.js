import React from 'react';

import { appState, isNotLoggedInCostlocker, isNotLoggedInFakturoid } from './state';
import { fetchFromApi, pushToApi, loginUrls } from './api';
import { trans } from './i18n';
import Form from './app/Form';
import Login from './app/Login';
import Invoice from './app/Invoice';
import InvoicesList from './app/InvoicesList';
import InvoiceLines from './app/InvoiceLines'
import Loading from './ui/Loading';

export let redirectToRoute = (route) => console.log('app is not ready', route);
export let isRouteActive = () => false;
const setError = e => appState.cursor(['app']).set('error', e);
const notify = (message) => alert(trans(message));

const fetchUser = () =>
  fetchFromApi(`/user`)
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
      if (user.costlocker) {
        pushToApi('/oauth/refresh').then(r => console.log(r));
      }
    });

if (isNotLoggedInCostlocker()) {
  fetchUser();
}

let lastQuery = null;

const fetchInvoices = (customFilter, wasInvoiceCreated) => {
  const filter = customFilter ? customFilter : appState.cursor(['search']).deref();
  const query = `/costlocker?type=${filter.get('type')}&query=${filter.get('query')}`;
  if (query === lastQuery && !wasInvoiceCreated) {
    return;
  }
  lastQuery = query;
  return fetchFromApi(query)
    .catch(setError)
    .then(invoices => appState.cursor().update(
      app => app
        .setIn(['costlocker', 'invoices'], invoices)
        .setIn(['search', 'isSearching'], false)
    ));
}

let timeout = null;
const fulltextInvoiceSearch = () => {
  fetchInvoices();
  timeout = null;
};

appState.on('next-animation-frame', function (newStructure, oldStructure, keyPath) {
  const isSearchUpdated = (field, keyPath) =>
    keyPath.length === 1 &&
    keyPath[0] === 'search' &&
    newStructure.getIn(['search', field]) !== oldStructure.getIn(['search', field]);

  if (isSearchUpdated('type', keyPath)) {
    fetchInvoices(newStructure.get('search'));
  } else if (isSearchUpdated('query', keyPath) && !timeout) {
    timeout = setTimeout(fulltextInvoiceSearch, 400);
  } else if (isSearchUpdated('isSearching', keyPath)) {
    appState.cursor(['search']).set('isSearching', false);
  }
});

const fetchInvoice = ({ project, billing, amount }, wasInvoiceCreated) => {
  fetchInvoices(null, wasInvoiceCreated);
  return fetchFromApi(`/costlocker?project=${project || ''}&billing=${billing || ''}&amount=${amount || ''}&query=billing`)
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

const reloadSubjects = () =>
  pushToApi('/fakturoid?action=downloadSubjects', {})
    .catch(setError)
    .then(() => fetchSubjects().then(() => notify('notify.reloadSubjects')));

export const states = [
  {
    name: 'homepage',
    url: '/?loginError',
    redirectTo: 'login',
  },
  {
    name: 'invoice',
    url: '/invoice?project&billing&amount',
    data: {
      title: (params) => {
        if (!params.billing || !params.project) {
          return 'page.invoices';
        }
        return 'page.invoice';
      },
    },
    component: (props) => {
      const params = props.transition.params();
      const subjects = appState.cursor(['fakturoid', 'subjects']).deref();
      const invoice = appState.cursor(['costlocker', 'invoice']).deref();
      if (!subjects || !invoice) {
        return <Loading title="loading.invoice" />;
      }
      if (appState.cursor(['app', 'isSendingForm']).deref()) {
        return <Loading title="loading.createInvoice" />;
      }
      const dateFormat = appState.cursor(['app', 'dateFormat']).deref();
      return <Invoice
        invoice={invoice}
        fakturoidSubjects={subjects}
        invoices={
          <InvoicesList
            invoices={appState.cursor(['costlocker', 'invoices']).deref()}
            subjects={subjects}
            dateFormat={dateFormat}
            form={new Form({
              stateKey: 'search',
              alwaysSet: (s) => s.setIn(['isSearching'], true),
            })}
            isLastCreatedInvoice={id => id === appState.cursor(['app', 'lastCreatedInvoice']).deref()}
          />
        }
        lines={new InvoiceLines(
          appState.cursor(['invoice', 'lines']),
          appState.cursor(['auth', 'fakturoid']).deref().account.hasVat,
          trans('invoiceLines.units')
        )}
        dateFormat={dateFormat}
        forceUpdate={() => appState.cursor(['invoice']).set('isForced', true)}
        form={new Form({
          stateKey: 'invoice',
          submit: () => {
            const request = {
              fakturoid: appState.cursor(['invoice']).deref().toJS(),
              costlocker: appState.cursor(['costlocker', 'invoice']).deref().costlocker,
            };
            appState.cursor(['app']).set('isSendingForm', true);
            pushToApi('/fakturoid?action=createInvoice', request)
              .catch(error => error.response.json())
              .then((createdInvoice) => {
                if (!createdInvoice || !createdInvoice.id) {
                  appState.cursor(['app']).set('isSendingForm', false);
                  const encodedError = JSON.stringify(createdInvoice);
                  if (encodedError.indexOf('Kontakt neexistuje.')) {
                    notify('notify.unknownSubject');
                    reloadSubjects();
                    return;
                  }
                  const error = new Error('Invoice not created');
                  error.stack = `${error.stack}\n${encodedError}`;
                  setError(error);
                  return;
                }
                fetchInvoice(params, true).then(() => {
                  appState.cursor().update(
                    app => app
                      .setIn(['app', 'isSendingForm'], false)
                      .setIn(['app', 'lastCreatedInvoice'], createdInvoice.id)
                      .setIn(['invoice', 'isForced'], false)
                  );
                  if (createdInvoice.update.hasFailed) {
                    appState.cursor().update(app => app.setIn(['app', 'activeTab'], 'invoices'));
                    redirectToRoute('invoice', {
                      project: null,
                      billing: null,
                      amount: null,
                    });
                    return;
                  }
                  redirectToRoute('invoice', {
                    project: params.project,
                    billing: createdInvoice.billing_id,
                    amount: null,
                  });
                });
              });
          },
        })}
        reloadSubjects={(e) => {
          e.preventDefault();
          reloadSubjects();
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
          fetchInvoice(params);
          fetchInvoices();
        }
      }
    ],
  },
  {
    name: 'login',
    url: '/login?loginError',
    data: {
      title: 'page.login'
    },
    component: (props) => <Login
      costlockerAuth={appState.cursor(['auth', 'costlocker']).deref()}
      fakturoidAuth={appState.cursor(['auth', 'fakturoid']).deref()}
      isLoggedInFakturoid={appState.cursor(['auth', 'isLoggedInFakturoid']).deref()}
      switchForm={new Form('app')}
      loginUrls={loginUrls}
      loginError={props.transition.params().loginError}
      form={new Form('fakturoid')} />,
    resolve: [
      {
        token: 'resetFakturoidForm',
        resolveFn: () => appState.cursor(['app']).set('isFakturoidLoginHidden', true),
      },
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
    callback: (transition) => {
      transition.abort();
      redirectToRoute('login', transition.params());
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
        notify('notify.requiredFakturoid');
      }
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
      document.title = `${trans(getTitle(params))} | Costlocker → Fakturoid`;
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
