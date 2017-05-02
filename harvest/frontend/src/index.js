import React from 'react';
import ReactDOM from 'react-dom';
import { UIRouter, UIView } from 'ui-router-react';

import { appState } from './state';
import { Router } from './Router';

const render = () =>
  ReactDOM.render(
    <UIRouter router={Router}>
      <UIView />
    </UIRouter>,
    document.getElementById('root')
  );

appState.on('next-animation-frame', function (newStructure, oldStructure, keyPath) {
  console.log('Subpart of structure swapped.', keyPath, JSON.stringify(newStructure.getIn(keyPath), null, 2));
  render();
});


render();
