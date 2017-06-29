import immstruct from 'immstruct';

const appState = immstruct({
  auth: {
    isLoading: true,
    costlocker: null,
    fakturoid: null,
  },
  app: {
    csrfToken: null,
    currentState: '', // helper for active menu items
    error: null,
  }
});

const isNotLoggedInCostlocker = () =>
  appState.cursor(['auth', 'costlocker']).deref() === null;

const isNotLoggedInFakturoid = () =>
  appState.cursor(['auth', 'fakturoid']).deref() === null;

const getCsrfToken = () => appState.cursor(['app', 'csrfToken']).deref();

export { appState, isNotLoggedInCostlocker, isNotLoggedInFakturoid, getCsrfToken };
