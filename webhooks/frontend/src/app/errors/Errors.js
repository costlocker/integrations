
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
    this.appState.cursor(['app']).update(
      app => app
        .setIn(['errors'], response.errors)
        .setIn(['isSendingForm'], false)
    );
    return true;
  };

  reset = (app) => app.setIn(['app', 'errors'], []);
}
