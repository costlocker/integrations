import immstruct from 'immstruct';

const appState = immstruct({
  auth: {
    isLoading: true,
    costlocker: null,
    fakturoid: null,
    isLoggedInFakturoid: false,
  },
  app: {
    csrfToken: null,
    currentState: '', // helper for active menu items
    error: null,
    isSendingForm: false,
  },
  fakturoid: {
    subjects: null,
  },
  costlocker: {
    invoice: null,
  },
  invoice: {
    isForced: false,
    subject: '',
    lines: []
  },
});

const isNotLoggedInCostlocker = () =>
  appState.cursor(['auth', 'costlocker']).deref() === null;

const isNotLoggedInFakturoid = () =>
  appState.cursor(['auth', 'isLoggedInFakturoid']).deref() === false;

const getCsrfToken = () => appState.cursor(['app', 'csrfToken']).deref();

export { appState, isNotLoggedInCostlocker, isNotLoggedInFakturoid, getCsrfToken };
