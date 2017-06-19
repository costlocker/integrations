import immstruct from 'immstruct';
import { Set } from 'immutable';
import { defaultSyncSettings } from './app/SyncSettings';

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
    companies: [],
  },
  currentState: '', // helper for active menu items
  events: null,
  companySettings: {
    isCostlockerWebhookEnabled: false,
    isCreatingBasecampProjectEnabled: false,
    account: null,
    costlockerUser: null,
    ...defaultSyncSettings,
  },
  sync: {
    account: null,
    costlockerProject: Set(),
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

export { appState, isNotLoggedInCostlocker, isNotLoggedInBasecamp };
