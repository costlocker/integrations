import React from 'react';
import { Set } from 'immutable';

import { appState, isNotLoggedInCostlocker, endpoints, session } from './state';
import { fetchFromCostlocker, pushToCostlocker } from './api';
import { PageWithSubpages } from './ui/App';
import Login from './app/Login';
import Webhooks from './app/Webhooks';
import { Webhook } from './app/Webhook';
import WebhookForm from './app/WebhookForm';
import WebhookDelete from './app/WebhookDelete';
import WebhookExample from './app/WebhookExample';
import WebhookDeliveries from './app/WebhookDeliveries';
import Errors from './app/errors/Errors';
import ErrorsView from './app/errors/ErrorsView';

export let redirectToRoute = (route) => console.log('app is not ready', route);
export let isRouteActive = () => false;

const setError = e => appState.cursor(['app']).set('error', e);
const errors = new Errors(appState);

const fetchUser = () => {
  const data = appState.cursor(['login']).deref();
  if (!data.get('host') || !data.get('token')) {
    appState.cursor().update(
      app => app
        .setIn(['auth', 'isLoading'], false)
        .setIn(['auth', 'costlocker'], null)
    );
    return;
  }
  return fetchFromCostlocker(endpoints.me())
    .catch(error => error.response.json())
    .then((response) => {
      if (errors.loadErrorsFromApiResponse(response)) {
        appState.cursor().update(
          app => app
            .setIn(['auth', 'isLoading'], false)
            .setIn(['auth', 'costlocker'], null)
        );
        return false;
      }
      session.login(data.toJS());
      appState.cursor().update(
        app => app
          .setIn(['auth', 'isLoading'], false)
          .setIn(['auth', 'costlocker'], response.data)
      );
      return true;
    });
}

if (isNotLoggedInCostlocker()) {
  fetchUser();
}

const fetchWebhooks = () =>
  fetchFromCostlocker(endpoints.webhooks())
    .catch(setError)
    .then(webhooks => {
      appState.cursor(['webhooks']).set('list', webhooks.data);
      return webhooks.data;
    });

const fetchWebhookDetail = (webhook, type) =>
  fetchFromCostlocker(endpoints.webhook(webhook, type), type === 'example')
    .catch(setError)
    .then(response => appState.cursor(['webhooks']).set(type, response));

const webhookFormToJS = () => appState.cursor(['webhook']).deref().toJS();
const webhookTitle = (title) => (getWebhook) => `${title} > ${getWebhook().url}`;

export const states = [
  {
    name: 'homepage',
    url: '/',
    redirectTo: 'login',
  },
  {
    name: 'webhooks',
    url: '/webhooks',
    redirectTo: 'webhooks.list',
    component: (props) => <PageWithSubpages
    pages={[
      {
        name: 'Webhooks',
        route: 'webhooks.list',
      },
      {
        name: 'Create a webhook',
        route: 'webhooks.create',
      },
    ]}
    content={view => view}
  />,
  },
  {
    name: 'webhooks.list',
    url: '/',
    data: {
      title: 'Webhooks',
      endpoint: endpoints.webhooks(),
    },
    component: (props) => <Webhooks
      webhooks={appState.cursor(['webhooks', 'list']).deref()}
    />,
    resolve: [
      {
        token: 'loadWebhooks',
        resolveFn: () => {
          if (!appState.cursor(['webhooks', 'list']).deref()) {
            fetchWebhooks();
          }
        }
      },
    ],
  },
  {
    name: 'webhooks.create',
    url: '/create',
    data: {
      title: 'Create a webhook',
      method: 'POST',
      endpoint: endpoints.webhooks(),
      request: webhookFormToJS,
    },
    component: (props) => <WebhookForm
      errors={<ErrorsView errors={errors} />}
      form={{
        get: (type) => appState.cursor(['webhook', type]).deref(),
        set: (type) => (e) => appState.cursor(['webhook']).set(
          type,
          e.target.type === 'checkbox' ? e.target.checked : e.target.value
        ),
        checkEvent: (e) => {
          appState.cursor(['webhook', 'events']).update(
            set => e.target.checked ? set.add(e.target.value) : set.delete(e.target.value)
          );
        },
        submit: (e) => {
          e.preventDefault();
          const definition = props.transition.to().data;
          pushToCostlocker(definition.endpoint, definition.request(props.transition.params()))
            .catch(error => error.response.json())
            .then((response) => {
              if (errors.loadErrorsFromApiResponse(response)) {
                return;
              }
              fetchWebhooks().then(() => redirectToRoute('webhook.example', { uuid: response.data[0].uuid }));
            })
        },
      }}
    />,
    resolve: [
      {
        token: 'resetForm',
        resolveFn: () => {
          appState.cursor(['webhook']).update(
            form => form
              .setIn(['url'], '')
              .setIn(['events'], Set())
          );
        }
      },
    ],
  },
  {
    name: 'webhook',
    url: '/webhooks/:uuid',
    redirectTo: 'webhook.example',
    component: (props) => <Webhook
      webhook={props.resolves.currentWebhook()}
    />,
    resolve: [
      {
        token: 'webhookIndex',
        deps: ['$transition$'],
        resolveFn: async ($transition$) => {
          const uuid = $transition$.params().uuid;
          let index = null;
          const webhooks = appState.cursor(['webhooks', 'list']).deref() || await fetchWebhooks();
          webhooks.forEach((w, i) => {
            if (w.uuid === uuid) {
              index = i;
            }
          })
          if (index === null) {
            redirectToRoute('webhooks');
            return;
          }
          return index;
        },
      },
      {
        token: 'currentWebhook',
        deps: ['webhookIndex'],
        resolveFn: (webhookIndex) => () => appState.cursor(['webhooks', 'list']).deref()[webhookIndex],
      },
    ],
  },
  {
    name: 'webhook.example',
    url: '/example',
    data: {
      title: webhookTitle('Webhook example'),
      endpoint: (currentWebhook) => endpoints.webhook(currentWebhook, 'example'),
    },
    component: (props) => <WebhookExample
      webhook={props.resolves.loadWebhook}
      example={appState.cursor(['webhooks', 'example']).deref()}
    />,
    resolve: [
      {
        token: 'loadWebhook',
        deps: ['$transition$'],
        resolveFn: async ($transition$) => {
          const uuid = $transition$.params().uuid;
          let webhook = null;
          const webhooks = appState.cursor(['webhooks', 'list']).deref() || await fetchWebhooks();
          webhooks.forEach(w => {
            if (w.uuid === uuid) {
              webhook = w;
            }
          })
          if (!webhook) {
            redirectToRoute('webhooks');
            return;
          }
          fetchWebhookDetail(webhook, 'example');
          return webhook;
        }
      },
    ],
  },
  {
    name: 'webhook.deliveries',
    url: '/deliveries',
    data: {
      title: webhookTitle('Recent deliveries'),
      endpoint: (currentWebhook) => endpoints.webhook(currentWebhook, 'webhook'),
    },
    component: (props) => <WebhookDeliveries
      webhook={props.resolves.loadWebhook}
      detail={appState.cursor(['webhooks', 'webhook']).deref()}
    />,
    resolve: [
      {
        token: 'loadWebhook',
        deps: ['$transition$'],
        resolveFn: async ($transition$) => {
          const uuid = $transition$.params().uuid;
          let webhook = null;
          const webhooks = appState.cursor(['webhooks', 'list']).deref() || await fetchWebhooks();
          webhooks.forEach(w => {
            if (w.uuid === uuid) {
              webhook = w;
            }
          })
          if (!webhook) {
            redirectToRoute('webhooks');
            return;
          }
          fetchWebhookDetail(webhook, 'webhook');
          return webhook;
        }
      },
    ],
  },
  {
    name: 'webhook.update',
    url: '/update',
    data: {
      title: webhookTitle('Update a webhook'),
      method: 'POST',
      endpoint: endpoints.webhooks(),
      request: (currentWebhook) => ({
        uuid: currentWebhook.uuid,
        ...webhookFormToJS(),
      }),
    },
    component: (props) => <WebhookForm
      updatedWebhook={props.resolves.loadWebhook}
      errors={<ErrorsView errors={errors} />}
      form={{
        get: (type) => appState.cursor(['webhook', type]).deref(),
        set: (type) => (e) => appState.cursor(['webhook']).set(
          type,
          e.target.type === 'checkbox' ? e.target.checked : e.target.value
        ),
        checkEvent: (e) => {
          appState.cursor(['webhook', 'events']).update(
            set => e.target.checked ? set.add(e.target.value) : set.delete(e.target.value)
          );
        },
        submit: (e) => {
          e.preventDefault();
          const definition = props.transition.to().data;
          pushToCostlocker(definition.endpoint, definition.request(props.transition.params()))
            .catch(error => error.response.json())
            .then((response) => {
              if (errors.loadErrorsFromApiResponse(response)) {
                return;
              }
              fetchWebhooks().then(() => redirectToRoute('webhook.example', { uuid: response.data[0].uuid }));
            })
        },
      }}
    />,
    resolve: [
      {
        token: 'loadWebhook',
        deps: ['$transition$'],
        resolveFn: async ($transition$) => {
          const uuid = $transition$.params().uuid;
          let webhook = null;
          const webhooks = appState.cursor(['webhooks', 'list']).deref() || await fetchWebhooks();
          webhooks.forEach(w => {
            if (w.uuid === uuid) {
              webhook = w;
            }
          })
          if (!webhook) {
            redirectToRoute('webhooks');
            return;
          }
          appState.cursor(['webhook']).update(
            form => form
              .setIn(['url'], webhook.url)
              .setIn(['events'], Set(webhook.events))
          );
          return webhook;
        }
      },
    ],
  },
  {
    name: 'webhook.delete',
    url: '/delete',
    data: {
      title: webhookTitle('Delete a webhook'),
      method: 'DELETE',
      endpoint: (currentWebhook) => endpoints.webhook(currentWebhook, 'webhook'),
    },
    component: (props) => <WebhookDelete
      errors={<ErrorsView errors={errors} />}
      deleteWebhook={(e) => {
        e.preventDefault();
        pushToCostlocker(props.resolves.loadWebhook.links.webhook, 'DELETE')
          .catch(error => error.response.json())
          .then((response) => {
            if (errors.loadErrorsFromApiResponse(response)) {
              return;
            }
            fetchWebhooks().then(() => redirectToRoute('webhooks'));
          })
      }}
    />,
    resolve: [
      {
        token: 'loadWebhook',
        deps: ['$transition$'],
        resolveFn: async ($transition$) => {
          const uuid = $transition$.params().uuid;
          let webhook = null;
          const webhooks = appState.cursor(['webhooks', 'list']).deref() || await fetchWebhooks();
          webhooks.forEach(w => {
            if (w.uuid === uuid) {
              webhook = w;
            }
          })
          if (!webhook) {
            redirectToRoute('webhooks');
            return;
          }
          return webhook;
        }
      },
    ],
  },
  {
    name: 'login',
    url: '/login',
    data: {
      title: 'Login',
      endpoint: endpoints.me(),
    },
    component: (props) => <Login
      costlockerAuth={appState.cursor(['auth', 'costlocker']).deref()}
      errors={<ErrorsView errors={errors} />}
      logout={() => {
        session.logout();
        appState.cursor(['auth']).set('costlocker', null);
      }}
      form={{
        get: (type) => appState.cursor(['login', type]).deref(),
        set: (type) => (e) => appState.cursor(['login']).set(
          type,
          e.target.type === 'checkbox' ? e.target.checked : e.target.value
        ),
        submit: (e) => {
          e.preventDefault();
          fetchUser().then((isLoggedIn) => isLoggedIn ? redirectToRoute('webhooks') : null);
        },
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
      transition.abort();
      redirectToRoute('login');
    },
    priority: 10,
  },
  {
    event: 'onSuccess',
    criteria: { to: state => state.data && state.data.title },
    callback: (transition) => {
      const state = transition.to();
      const stateTitle = state.data.title;
      const getTitle = typeof stateTitle === 'function' ? stateTitle : () => stateTitle;
      const getWebhook = transition.injector().get('currentWebhook') || (() => null);
      document.title = `${getTitle(getWebhook)} | Costlocker Webhooks`;
      appState.cursor([]).update(
        app => errors.reset(app)
          .setIn(['app', 'currentState'], state.name) // rerender menu - stateService.go reloads only <UIView>
          .setIn(['curl', 'method'], state.data.method || 'GET')
          .setIn(['curl', 'endpoint'], typeof state.data.endpoint === 'function' ? state.data.endpoint(getWebhook()) : state.data.endpoint)
          .setIn(['curl', 'request'], state.data.method === 'POST' ? () => state.data.request(getWebhook()) : null)
      );
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
  isRouteActive = (route) => {
    if (route === 'webhook' || route === 'webhooks') {
      return router.stateService.current.name.indexOf(`${route}.`) !== -1;
    }
    return router.stateService.is(route);
  };
  hooks.forEach(hook => router.transitionService[hook.event](hook.criteria, hook.callback, { priority: hook.priority }));
}
