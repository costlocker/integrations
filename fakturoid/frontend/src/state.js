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
    isDisabled: false,
    csrfToken: null,
    currentState: null, // helper for active menu items
    activeTab: '',
    error: null,
    isSendingForm: false,
    lastCreatedInvoice: null,
  },
  fakturoid: {
    slug: null,
    subjects: null,
  },
  costlocker: {
    invoice: null,
    latestInvoices: [],
    projectInvoices: [],
  },
  invoice: {
    isForced: false,
    subject: '',
    note: '',
    type: 'invoice',
    hasVat: false,
    vat: 21,
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
