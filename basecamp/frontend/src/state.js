import immstruct from 'immstruct';

const appState = immstruct({
  auth: {
    costlocker: null,
    basecamp: null,
  },
  costlocker: {
    projects: null,
  },
  basecamp: {
    projects: [],
  },
});

const isNotLoggedIn = () =>
  appState.cursor(['auth', 'costlocker']).deref() === null;

export { appState, isNotLoggedIn };
