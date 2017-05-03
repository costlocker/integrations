import immstruct from 'immstruct';

const appState = immstruct({
  auth: {
    harvest: null,
    costlocker: null,
  },
  harvest: {
    projects: [],
    selectedProject: null,
    peopleCosts: null,
    expenses: null,
    billing: null,
  },
});

const isNotLoggedIn = () =>
  appState.cursor(['auth', 'harvest']).deref() === null ||
  appState.cursor(['auth', 'costlocker']).deref() === null;

export { appState, isNotLoggedIn };
