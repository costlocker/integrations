import immstruct from 'immstruct';
import { OrderedMap } from 'immutable';

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
    lines: OrderedMap(),
  },
  subject: {
    name: '',
  },
});

const isNotLoggedInCostlocker = () =>
  appState.cursor(['auth', 'costlocker']).deref() === null;

const isNotLoggedInFakturoid = () =>
  appState.cursor(['auth', 'isLoggedInFakturoid']).deref() === false;

const getCsrfToken = () => appState.cursor(['app', 'csrfToken']).deref();

export { appState, isNotLoggedInCostlocker, isNotLoggedInFakturoid, getCsrfToken };
