import React from 'react';

import OAuthLogin from './OAuthLogin';
import { ExternalLink } from '../Helpers';

export default function Login({ auth, loginUrls, clLoginError }) {
  return (
    <div>
      <div className="row text-center">
        <div className="col-sm-12">
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
