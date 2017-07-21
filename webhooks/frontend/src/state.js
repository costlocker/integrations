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
    token: null,
    host: 'https://new.costlocker.com',
  },
});

const isNotLoggedInCostlocker = () =>
  appState.cursor(['auth', 'costlocker']).deref() === null;

const apiUrl = () => appState.cursor(['login', 'host']).deref();

export { appState, isNotLoggedInCostlocker, apiUrl };
