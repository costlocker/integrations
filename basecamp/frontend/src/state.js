import immstruct from 'immstruct';

const appState = immstruct({
  auth: {
    isLoading: true,
    costlocker: null,
    basecamp: null,
    settings: null,
  },
  costlocker: {
    projects: null,
  },
  basecamp: {
    projects: [],
  },
  currentState: '', // helper for active menu items
  events: null,
  companySettings: {
    areTodosEnabled: true,
    isDeletingTodosEnabled: false,
    isRevokeAccessEnabled: false,
    isCostlockerWebhookEnabled: false,
  },
  sync: {
    account: null,
    costlockerProject: '',
    basecampProject: '',
    areTodosEnabled: true,
    isDeletingTodosEnabled: false,
    isRevokeAccessEnabled: false,
    mode: 'create',
  }
});

const isNotLoggedInCostlocker = () =>
  appState.cursor(['auth', 'costlocker']).deref() === null;

const isNotLoggedInBasecamp = () =>
  appState.cursor(['auth', 'basecamp']).deref() === null;

export { appState, isNotLoggedInCostlocker, isNotLoggedInBasecamp };
