import React from 'react';

import OAuthLogin from './OAuthLogin';
import { FullButton, ExternalLink } from '../Helpers';

export default function Login({ isLoggedIn, auth, goToNextStep, loginUrls, clLoginError, harvestLoginError }) {
  let navigation = null;
  if (isLoggedIn) {
      navigation = <div className="row">
        <div className="col-sm-12">
          <FullButton text="Continue to Projects" onClick={goToNextStep} />
        </div>
      </div>;
  }
  return (
    <div>
      {navigation}
      <div className="row text-center">
        <div className="col-sm-6">
          <h2>Harvest <ExternalLink url="https://getharvest.com" /></h2>
          <OAuthLogin
            title={auth.harvest
              ? <span>Switch account <strong>{auth.harvest.user_name}</strong></span>
              : 'Login to Harvest'}
            loginError={harvestLoginError}
            loginUrl={loginUrls.harvest} />
        </div>
        <div className="col-sm-6">
          <h2>Costlocker <ExternalLink url="https://new.costlocker.com" /></h2>
          <OAuthLogin
            title={auth.costlocker
              ? <span>Switch account <strong>{auth.costlocker.person.first_name} {auth.costlocker.person.last_name}</strong></span>
              : 'Login to Costlocker'}
            loginError={clLoginError} loginUrl={loginUrls.costlocker} />
        </div>
      </div>
    </div>
  );
};
