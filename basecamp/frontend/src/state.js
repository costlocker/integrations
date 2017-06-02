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
  sync: {
    selectedAccount: null,
    costlockerProject: null,
    basecampProject: null,
    isProjectCreated: true,
    result: null,
  }
});

const isNotLoggedInCostlocker = () =>
  appState.cursor(['auth', 'costlocker']).deref() === null;

const isNotLoggedInBasecamp = () =>
  appState.cursor(['auth', 'basecamp']).deref() === null;

export { appState, isNotLoggedInCostlocker, isNotLoggedInBasecamp };
