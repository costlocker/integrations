import immstruct from 'immstruct';

const appState = immstruct({
  user: {
    harvest: {
      company_name: '',
      company_url: '',
      user_name: '',
      user_avatar: '',
    },
  },
  harvest: {
    projects: [],
    selectedProject: null,
  }
});

const isNotLoggedIn = () => appState.cursor(['user', 'harvest', 'company_name']).deref() === '';

export { appState, isNotLoggedIn };
