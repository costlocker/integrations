import React from 'react';
import ReactDOM from 'react-dom';
import { UIRouter, pushStateLocationPlugin } from '@uirouter/react';

import { isDevelopmentMode } from './config';
import { appState } from './state';
import { states, config, isRouteActive } from './Router';
import { addLocaleData } from './i18n';
import { App } from './ui/App';
import Loading from './ui/Loading';
import ErrorPage from './ui/ErrorPage';
import DisabledAddon from './ui/DisabledAddon';
import bootstrapComponentsAdapter from './ui/BootstrapComponents';
import './ui/index.css'
import { unregister } from './registerServiceWorker';

export const plugins = [pushStateLocationPlugin];

addLocaleData('cs');

const render = () => {
  let content = null;
  if (appState.cursor(['auth', 'isLoading']).deref()) {
    content = <Loading title='loading.app' />;
  } else if (appState.cursor(['app', 'isDisabled']).deref()) {
    content = <DisabledAddon user={appState.cursor(['auth', 'costlocker']).deref()} />;
  } else if (appState.cursor(['app', 'error']).deref()) {
    content = <ErrorPage appState={appState} isDevelopmentMode={isDevelopmentMode} />
  } else {
    content =
      <UIRouter states={states} config={config} plugins={plugins}>
        <App auth={appState.cursor(['auth']).deref()} isRouteActive={isRouteActive} />
      </UIRouter>;
  }
  ReactDOM.render(content, document.getElementById('root'));
}

appState.on('next-animation-frame', function (newStructure, oldStructure, keyPath) {
  if (isDevelopmentMode) {
    console.log('Subpart of structure swapped.', keyPath, JSON.stringify(newStructure.getIn(keyPath), null, 2));
  }
  render();
});

render();
bootstrapComponentsAdapter();
unregister();
