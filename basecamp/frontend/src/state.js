import immstruct from 'immstruct';
import { Set } from 'immutable';

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
    areTodosEnabled: true,
    isDeletingTodosEnabled: false,
    isRevokeAccessEnabled: false,
    isCostlockerWebhookEnabled: false,
    isCreatingBasecampProjectEnabled: false,
    account: null,
    costlockerUser: null,
    // basecamp -> costlocker
    areTasksEnabled: false,
    isDeletingTasksEnabled: false,
    isBasecampWebhookEnabled: false,
  },
  sync: {
    account: null,
    costlockerProject: Set(),
    basecampProject: '',
    basecampClassicCompanyId: '',
    areTodosEnabled: true,
    isDeletingTodosEnabled: false,
    isRevokeAccessEnabled: false,
    mode: 'create',
    // basecamp -> costlocker
    areTasksEnabled: false,
    isDeletingTasksEnabled: false,
    isBasecampWebhookEnabled: false,
  }
});

const isNotLoggedInCostlocker = () =>
  appState.cursor(['auth', 'costlocker']).deref() === null;

const isNotLoggedInBasecamp = () =>
  appState.cursor(['auth', 'basecamp']).deref() === null;

export { appState, isNotLoggedInCostlocker, isNotLoggedInBasecamp };
