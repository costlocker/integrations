import immstruct from 'immstruct';
import { OrderedSet } from 'immutable';
import { defaultSyncSettings } from './app/SyncSettings';

const appState = immstruct({
  auth: {
    isLoading: true,
    costlocker: null,
    basecamp: null,
    settings: null,
  },
  app: {
    isDisabled: false,
    csrfToken: null,
    currentState: null, // helper for active menu items
    error: null,
  },
  costlocker: {
    projects: null,
  },
  basecamp: {
    isAccountAvailable: true,
    projects: [],
    companies: [],
  },
  events: null,
  companySettings: {
    isCreatingBasecampProjectEnabled: false,
    account: null,
    costlockerUser: null,
    ...defaultSyncSettings,
  },
  sync: {
    account: null,
    costlockerProject: OrderedSet(),
    basecampProject: '',
    basecampClassicCompanyId: '',
    mode: 'create',
    ...defaultSyncSettings,
  }
});

const isNotLoggedInCostlocker = () =>
  appState.cursor(['auth', 'costlocker']).deref() === null;

const isNotLoggedInBasecamp = () =>
  appState.cursor(['auth', 'basecamp']).deref() === null;

const getCsrfToken = () => appState.cursor(['app', 'csrfToken']).deref();

export { appState, isNotLoggedInCostlocker, isNotLoggedInBasecamp, getCsrfToken };
