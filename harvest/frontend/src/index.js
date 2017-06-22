import React from 'react';
import ReactDOM from 'react-dom';
import { UIRouter, UIView, pushStateLocationPlugin } from 'ui-router-react';
import { Visualizer } from 'ui-router-visualizer';

import { isDevelopmentMode } from './config';
import { appState } from './state';
import { states, config } from './Router';
import './ui/index.css'

export const plugins = isDevelopmentMode ? [pushStateLocationPlugin, Visualizer] : [pushStateLocationPlugin];

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
