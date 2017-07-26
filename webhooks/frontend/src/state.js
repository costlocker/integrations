import immstruct from 'immstruct';
import { Set } from 'immutable';
import Session from './app/Session';

const session = new Session();

const appState = immstruct({
  auth: {
    isLoading: true,
    costlocker: null,
  },
  app: {
    currentState: null,
    // form
    errors: null,
    isSendingForm: false,
  },
  curl: {
    method: null,
    endpoint: null,
    request: null,
  },
  login: session.getCurrentUser(),
  webhooks: {
    list: null,
    example: null,
    webhook: null,
  },
  webhook: {
    url: '',
    events: Set(),
  },
});

const isNotLoggedInCostlocker = () =>
  appState.cursor(['auth', 'costlocker']).deref() === null;

const apiAuth = () => {
  const credentials = `costlocker/webhooks:${appState.cursor(['login', 'token']).deref()}`;
  return {
    'Authorization': `Basic ${btoa(credentials)}`,
  };
};

const apiUrl = (path) => {
  const apiUrl = `${appState.cursor(['login', 'host']).deref()}/api-public/v2`;
  return `${apiUrl}${path}`;
};

const endpoints = {
  me: () => apiUrl('/me'),
  webhooks: () => apiUrl('/webhooks'),
  webhook: (webhook, type) => webhook.links[type],
};

export { appState, isNotLoggedInCostlocker, apiAuth, endpoints, session };
