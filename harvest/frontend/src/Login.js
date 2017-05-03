import React from 'react';

import LoginForm from './harvest/LoginForm';

const anonymousUser = <em>Not logged in</em>;

export default function Login({ isLoggedIn, auth, goToNextStep, handleHarvestLogin, loginUrl, clLoginError }) {
  let navigation = <hr />;
  if (isLoggedIn) {
      navigation =
        <div className="bg-success">
          <hr />
          <button className="btn btn-success btn-lg" onClick={goToNextStep}>
            Continue to Projects
          </button>
          <hr />
        </div>;
  }
  const costlockerLogin = <a href={loginUrl} className="btn btn-primary">Login to Costlocker</a>;
  return (
    <div>
      <div className="row">
        <div className="col-sm-6">
          <h2>Harvest User</h2>
          {auth.harvest ? <strong>{auth.harvest.user_name}</strong> : anonymousUser}
        </div>
        <div className="col-sm-6">
          <h2>Costlocker User</h2>
          {auth.costlocker ? <strong>{auth.costlocker.access_token}</strong> : anonymousUser}
        </div>
      </div>
      <div className="row">
        <div className="col-sm-12 text-center">
          {navigation}
        </div>
      </div>
      <div className="row">
        <div className="col-sm-6">
          <LoginForm handleHarvestLogin={handleHarvestLogin} />
        </div>
        <div className="col-sm-6">
          <h2>Change Costlocker Account</h2>
          {clLoginError ? <p className="bg-danger"><strong>Login error</strong>:<br />{decodeURIComponent(clLoginError)}</p> : ''}
          {costlockerLogin}
        </div>
      </div>
    </div>
  );
};
