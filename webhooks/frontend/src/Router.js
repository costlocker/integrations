import React from 'react';

import { appState, isNotLoggedInCostlocker } from './state';
import { fetchFromApi, pushToApi } from './api';
import Login from './app/Login';
import Webhooks from './app/Webhooks';
import Webhook from './app/Webhook';
import WebhookForm from './app/WebhookForm';
import WebhookDelete from './app/WebhookDelete';
import WebhookExample from './app/WebhookExample';
import WebhookDeliveries from './app/WebhookDeliveries';

export let redirectToRoute = (route) => console.log('app is not ready', route);
export let isRouteActive = () => false;
const setError = e => appState.cursor(['app']).set('error', e);

const fetchUser = () => {
  const data = appState.cursor(['login']).deref();
  if (!data.get('host') || !data.get('token')) {
    appState.cursor().update(
      app => app
        .setIn(['auth', 'isLoading'], false)
        .setIn(['auth', 'costlocker'], null)
        .setIn(['login', 'error'], null)
    );
    return;
  }
  fetchFromApi(`/me`)
    .catch(() => {
      appState.cursor().update(
        app => app
          .setIn(['auth', 'isLoading'], false)
          .setIn(['auth', 'costlocker'], null)
          .setIn(['login', 'error'], 'Invalid Token')
      );
    })
    .then((user) => {
      if (!user || !user.data) {
        return;
      }
      appState.cursor().update(
        app => app
          .setIn(['auth', 'isLoading'], false)
          .setIn(['auth', 'costlocker'], user.data)
          .setIn(['login', 'error'], null)
      );
    });
}

if (isNotLoggedInCostlocker()) {
  fetchUser();
}

const fetchWebhooks = () =>
  fetchFromApi('/webhooks')
    .catch(setError)
    .then(webhooks => {
      appState.cursor(['webhooks']).set('list', webhooks.data);
      return webhooks.data;
    });

const fetchWebhookDetail = (webhook, type) =>
  fetchFromApi(webhook.links[type], type === 'example')
    .catch(setError)
    .then(response => appState.cursor(['webhooks']).set(type, response));

const webhookFormToJS = () => appState.cursor(['webhook', 'request']).deref().toJS();

const reloadFormExample = () => (
  appState.on('next-animation-frame', function (newStructure, oldStructure, keyPath) {
    const newRequest = newStructure.get('webhook').get('request').toJS();
    const oldRequest = oldStructure.get('webhook').get('request').toJS();
    const isPostMethod = newStructure.get('curl').get('method') === 'POST';
    if (isPostMethod && oldRequest !== newRequest && keyPath !== ['curl', 'data']) {
      appState.cursor(['curl']).set('data', newRequest);
    }
  })
);

reloadFormExample();

export const states = [
  {
    name: 'homepage',
    url: '/',
    redirectTo: 'login',
  },
  {
    name: 'webhooks',
    url: '/webhooks',
    data: {
      title: 'Webhooks',
      api: '/webhooks',
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
    name: 'webhooks-create',
    url: '/webhooks/create',
    data: {
      title: 'Create a webhook',
      api: '/webhooks',
      method: 'POST',
      request: webhookFormToJS,
    },
    component: (props) => <WebhookForm
      form={{
        errors: () => appState.cursor(['webhook', 'errors']).deref(),
        get: (type) => appState.cursor(['webhook', 'request', type]).deref(),
        set: (type) => (e) => appState.cursor(['webhook', 'request']).set(
          type,
          e.target.type === 'checkbox' ? e.target.checked : e.target.value
        ),
        checkEvent: (e) => {
          appState.cursor(['webhook', 'request', 'events']).update(
            set => e.target.checked ? set.add(e.target.value) : set.delete(e.target.value)
          );
        },
        submit: (e) => {
          e.preventDefault();
          pushToApi('/webhooks', webhookFormToJS())
            .catch(error => error.response.json())
            .then((response) => {
              console.log(response, webhookFormToJS());
              if (response.errors) {
                appState.cursor(['webhook']).set('errors', response.errors);
                return;
              }
              fetchWebhooks().then(() => redirectToRoute('webhook.example', { uuid: response.data[0].uuid }));
            })
        },
      }}
    />,
  },
  {
    name: 'webhook',
    url: '/webhooks/:uuid',
    redirectTo: 'login',
    component: (props) => <Webhook
      webhook={props.resolves.loadWebhook}
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
    name: 'webhook.example',
    url: '/example',
    data: {
      title: 'Webhook Example',
      api: params => `/webhooks/${params.uuid}/test`,
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
      title: 'Webhook Example',
      api: params => `/webhooks/${params.uuid}`,
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
    name: 'webhook.delete',
    url: '/delete',
    data: {
      title: 'Webhook Delete',
      api: params => `/webhooks/${params.uuid}`,
      method: 'DELETE',
    },
    component: (props) => <WebhookDelete
      errors={appState.cursor(['webhook', 'errors']).deref()}
      deleteWebhook={(e) => {
        e.preventDefault();
        pushToApi(props.resolves.loadWebhook.links.webhook, 'DELETE')
          .catch(error => error.response.json())
          .then((response) => {
            if (response.errors) {
              appState.cursor(['webhook']).set('errors', response.errors);
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
      api: '/me',
    },
    component: (props) => <Login
      costlockerAuth={appState.cursor(['auth', 'costlocker']).deref()}
      form={{
        get: (type) => appState.cursor(['login', type]).deref(),
        set: (type) => (e) => appState.cursor(['login']).set(
          type,
          e.target.type === 'checkbox' ? e.target.checked : e.target.value
        ),
        submit: (e) => {
          e.preventDefault();
          fetchUser();
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
      transition.router.stateService.target('login', transition.params(), { location: true })
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
      document.title = `${getTitle(params)} | Costlocker Webhooks`;
      // rerender to change active state in menu - stateService.go reloads only <UIView>
      appState.cursor([]).update(
        app => app
          .setIn(['app', 'currentState'], state.name)
          .setIn(['curl', 'url'], typeof state.data.api === 'function' ? state.data.api(params) : state.data.api)
          .setIn(['curl', 'method'], state.data.method ? state.data.method : 'GET')
          .setIn(['curl', 'data'], state.data.request ? state.data.request(params) : null)
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
    if (route === 'webhook') {
      return router.stateService.current.name.indexOf('webhook.') !== -1;
    }
    return router.stateService.is(route);
  };
  hooks.forEach(hook => router.transitionService[hook.event](hook.criteria, hook.callback, { priority: hook.priority }));
}
