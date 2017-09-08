import React from 'react';
import ReactDOM from 'react-dom';
import { UIRouter, pushStateLocationPlugin } from '@uirouter/react';

import { isDevelopmentMode } from './config';
import { appState } from './state';
import { states, config } from './Router';
import { App } from './ui/App';
import Loading from './ui/Loading';
import ErrorPage from './ui/ErrorPage';
import CurlExample from './app/CurlExample';
import bootstrapComponentsAdapter from './ui/BootstrapComponents';
import './ui/index.css'
import registerServiceWorker from './registerServiceWorker';

export const plugins = [pushStateLocationPlugin];

const render = () => {
  let content = null;
  if (appState.cursor(['auth', 'isLoading']).deref()) {
    content = <Loading title='Loading Costlocker webhooks manager' />;
  } else if (appState.cursor(['app', 'error']).deref()) {
    content = <ErrorPage appState={appState} isDevelopmentMode={isDevelopmentMode} />
  } else {
    content =
      <UIRouter states={states} config={config} plugins={plugins}>
        <App auth={appState.cursor(['auth']).deref()} footer={<CurlExample appState={appState} />} />
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
registerServiceWorker();
