import immstruct from 'immstruct';
import { Set } from 'immutable';

const appState = immstruct({
  auth: {
    isLoading: true,
    costlocker: null,
  },
  app: {
    currentState: null,
    errors: null,
  },
  curl: {
    method: null,
    url: null,
    data: null,
  },
  login: {
    error: null,
    token: '',
    host: 'https://new-n1.costlocker.com',
  },
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

const apiUrl = (path) => {
  if (path.indexOf('http') === 0) {
    return path;
  }
  const apiUrl = `${appState.cursor(['login', 'host']).deref()}/api-public/v2`;
  return `${apiUrl}${path}`;
};

const apiAuth = () => {
  const credentials = `costlocker/webhooks:${appState.cursor(['login', 'token']).deref()}`;
  return {
    'Authorization': `Basic ${btoa(credentials)}`,
  };
};

export { appState, isNotLoggedInCostlocker, apiUrl, apiAuth };
