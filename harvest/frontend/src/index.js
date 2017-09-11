import React from 'react';
import ReactDOM from 'react-dom';
import { UIRouter, UIView, pushStateLocationPlugin } from '@uirouter/react';

import { isDevelopmentMode } from './config';
import { appState } from './state';
import { states, config } from './Router';
import bootstrapComponentsAdapter from './ui/BootstrapComponents';
import './ui/index.css'
import { unregister } from './registerServiceWorker';

export const plugins = [pushStateLocationPlugin];

const render = () =>
  ReactDOM.render(
    <UIRouter states={states} config={config} plugins={plugins}>
      <UIView />
    </UIRouter>,
    document.getElementById('root')
  );

appState.on('next-animation-frame', function (newStructure, oldStructure, keyPath) {
  if (isDevelopmentMode) {
    console.log('Subpart of structure swapped.', keyPath, JSON.stringify(newStructure.getIn(keyPath), null, 2));
  }
  render();
});

render();
bootstrapComponentsAdapter();
unregister();
