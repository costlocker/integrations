import React from 'react';
import ReactDOM from 'react-dom';
import immstruct from 'immstruct';
import { Map } from 'immutable';

import Login from './Login';
import { pushToApi } from './api';

const appState = immstruct({
  user: {
    harvest: {
      company_name: '',
      company_url: '',
      user_name: '',
      user_avatar: '',
    },
  },
});

const handleHarvestLogin = (props) => pushToApi('/harvest', props)
  .then(user => appState.cursor(['user']).set('harvest', Map(user)));

const render = () =>
  ReactDOM.render(
    <Login harvestUser={appState.cursor(['user', 'harvest'])} handleHarvestLogin={handleHarvestLogin} />,
    document.getElementById('root')
  );

appState.on('next-animation-frame', function (newStructure, oldStructure, keyPath) {
  console.log('Subpart of structure swapped.', keyPath, newStructure.toJSON());
  render();
});


render();
