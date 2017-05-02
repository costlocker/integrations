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
});

const isNotLoggedIn = () => appState.cursor(['user', 'harvest', 'company_name']).deref() === '';

export { appState, isNotLoggedIn };
