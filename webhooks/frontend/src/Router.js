import React from 'react';

import { appState, isNotLoggedInCostlocker } from './state';
import { fetchFromApi } from './api';
import Login from './app/Login';
import Webhooks from './app/Webhooks';
import Webhook from './app/Webhook';
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
    name: 'login',
    url: '/login',
    data: {
      title: 'Login',
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
      appState.cursor(['app']).update(
        app => app
          .setIn(['currentState'], state.name)
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
