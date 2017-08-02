import { appState } from '../state';

export default class Form {
  constructor(config) {
    if (Array.isArray(config)) {
      this.config = {
        keys: config,
        alwaysSet: s => s,
      };
    } else {
      this.config = config;
    }
  }

  get = (type) => this.cursor().deref().get(type);

  set = (type) =>
    (e) => this.cursor().update(
      state => this.config.alwaysSet(state)
        .setIn([type], e.target.type === 'checkbox' ? e.target.checked : e.target.value)
    );

  cursor = () => appState.cursor(this.config.keys);
}
