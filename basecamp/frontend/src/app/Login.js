import React from 'react';

import OAuthLogin from './OAuthLogin';
import { ExternalLink } from '../ui/Components';

export default function Login({ costlockerAuth, loginUrls, loginError }) {
  return (
    <div>
      <div className="row text-center">
        <div className="col-sm-6 col-sm-offset-3">
          <h2>Costlocker <ExternalLink url="https://new.costlocker.com" /></h2>
          <OAuthLogin
            title={costlockerAuth
              ? <span>Switch account <strong>{costlockerAuth.person.first_name} {costlockerAuth.person.last_name}</strong></span>
              : 'Login to Costlocker'}
            loginError={loginError} loginUrl={loginUrls.costlocker} />
        </div>
      </div>
    </div>
  );
};
