import immstruct from 'immstruct';

const appState = immstruct({
  auth: {
    isLoading: true,
    costlocker: null,
  },
  app: {
    currentState: null,
  },
  login: {
    error: null,
    token: '',
    host: 'https://new-n1.costlocker.com',
  },
});

const isNotLoggedInCostlocker = () =>
  appState.cursor(['auth', 'costlocker']).deref() === null;

const apiUrl = () => `${appState.cursor(['login', 'host']).deref()}/api-public/v2`;

const apiAuth = () => {
  const credentials = `costlocker/webhooks:${appState.cursor(['login', 'token']).deref()}`;
  return {
    'Authorization': `Basic ${btoa(credentials)}`,
  };
};

export { appState, isNotLoggedInCostlocker, apiUrl, apiAuth };
