import immstruct from 'immstruct';

const appState = immstruct({
  auth: {
    costlocker: null,
  },
});

const isNotLoggedIn = () =>
  appState.cursor(['auth', 'costlocker']).deref() === null;

export { appState, isNotLoggedIn };
