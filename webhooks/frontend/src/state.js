import immstruct from 'immstruct';

const appState = immstruct({
  auth: {
    isLoading: true,
    costlocker: null,
  },
  app: {
    currentState: null,
    apiEndpoint: null,
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
