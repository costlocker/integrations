import immstruct from 'immstruct';

const appState = immstruct({
  harvest: {
    user: null,
    projects: [],
    selectedProject: null,
    peopleCosts: null,
  }
});

const isNotLoggedIn = () => appState.cursor(['harvest', 'user']).deref() === null;

export { appState, isNotLoggedIn };
