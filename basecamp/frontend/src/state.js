import immstruct from 'immstruct';

const appState = immstruct({
  auth: {
    costlocker: null,
    basecamp: null,
  },
  costlocker: {
    projects: null,
  },
});

const isNotLoggedIn = () =>
  appState.cursor(['auth', 'costlocker']).deref() === null;

export { appState, isNotLoggedIn };
