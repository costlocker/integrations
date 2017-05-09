import immstruct from 'immstruct';

const appState = immstruct({
  auth: {
    harvest: null,
    costlocker: null,
  },
  harvest: {
    projects: null,
    selectedProject: null,
    peoplecosts: null,
    expenses: null,
    billing: null,
  },
  importResult: null,
  app: {
    harvestError: null,
  }
});

const isNotLoggedIn = () =>
  appState.cursor(['auth', 'harvest']).deref() === null ||
  appState.cursor(['auth', 'costlocker']).deref() === null;

export { appState, isNotLoggedIn };
