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

  set = (type) => (e) => this.update(state => state.set(type, e.target.type === 'checkbox' ? e.target.checked : e.target.value));

  toggle = (type) => () => this.update(state => state.set(type, !this.get(type)));

  checkItem = (type) =>
    (e) => this.update(
      state => {
        const set = this.get(type);
        return state.set(type, e.target.checked ? set.add(e.target.value) : set.delete(e.target.value));
      }
    );

  checkAll = (type, items) => () => this.update(
    state => {
      let set = this.get(type);
      items.forEach(item => {
        set = set.add(item);
      });
      return state.set(type, set);
    }
  );

  isChecked = (type, item) => this.get(type).contains(item);

  update = (updater) => this.cursor().update(
    state => {
      const updated = updater(state);
      return this.config.alwaysSet(updated);
    }
  );

  cursor = () => appState.cursor(this.config.keys);
}
