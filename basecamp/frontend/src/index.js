import React from 'react';
import ReactDOM from 'react-dom';
import { UIRouter, pushStateLocationPlugin } from 'ui-router-react';
import { Visualizer } from 'ui-router-visualizer';

import { isDevelopmentMode } from './config';
import { appState } from './state';
import { states, config, isRouteActive } from './Router';
import App from './ui/App';
import Loading from './ui/Loading';
import './ui/index.css'

export const plugins = isDevelopmentMode ? [pushStateLocationPlugin, Visualizer] : [pushStateLocationPlugin];

const render = () => {
  let content = null;
  if (appState.cursor(['auth', 'isLoading']).deref()) {
    content = <Loading title='Loading Costlocker & Basecamp integration' />
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
