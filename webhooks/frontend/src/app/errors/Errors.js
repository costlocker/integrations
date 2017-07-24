
export default class Errors {
  constructor(appState) {
    this.appState = appState;
  }

  hasNoErrors = () => this.getAll().length === 0;

  getAll = () => this.appState.cursor(['app', 'errors']).deref() || [];

  loadErrorsFromApiResponse = (response) => {
    if (!response.errors) {
      return false;
    }
    this.appState.cursor(['app']).set('errors', response.errors);
    return true;
  };

  reset = (app) => app.setIn(['app', 'errors'], []);
}
