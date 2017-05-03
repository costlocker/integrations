import React from 'react';

import LoginForm from './harvest/LoginForm';

export default function Login({ harvestUser, goToNextStep, handleHarvestLogin, loginUrl }) {
  let navigation = 'Login to Harvest and Costlocker before importing projects';
  if (harvestUser) {
      navigation =
        <button className="btn btn-success" onClick={goToNextStep}>
          Continue to Projects
        </button>;
  }
  const costlockerLogin = <a href={loginUrl} className="btn btn-primary">Login to Costlocker</a>;
  return (
    <div>
      <div className="row">
        <div className="col-sm-12 text-right">
          {navigation}
        </div>
      </div>
      <div className="row">
        <div className="col-sm-6">
          <h2>Harvest User</h2>
          {harvestUser ? <strong>{harvestUser.user_name}</strong> : <em>Not logged in</em>}
        </div>
        <div className="col-sm-6">
          <h2>Costlocker User</h2>
          costlocker user...
        </div>
      </div>
      <div className="row">
        <div className="col-sm-6">
          <LoginForm handleHarvestLogin={handleHarvestLogin} />
        </div>
        <div className="col-sm-6">
          <h2>Change Costlocker Account</h2>
          {costlockerLogin}
        </div>
      </div>
    </div>
  );
};
