import React from 'react';

import OAuthLogin from '../auth/OAuthLogin';
import { ExternalLink } from '../Helpers';

const BasecampAccounts = ({ basecamp }) => {
  return (
    <table className="table table-striped table-hover table-condensed">
      <thead>
        <tr>
          <th>Basecamp</th>
          <th>Version</th>
        </tr>
      </thead>
      <tbody>
        {basecamp.accounts.map(account => (
          <tr key={account.id}>
            <td>{account.name} <ExternalLink url={account.app_href} /></td>
            <td>{account.product}</td>
          </tr>
        ))}
      </tbody>
    </table>
  )
}

export default function Login({ basecamp, loginUrls }) {
  return (
    <div>
      <div className="row">
        <div className="col-sm-12">
          <h1>Basecamp</h1>
          <p><strong>Supported products:</strong> Basecamp 3, Basecamp 2, and Basecamp Classic</p>
        </div>
      </div>
      <div className="row">
        <div className="col-sm-12">
          <h3>Accounts</h3>
          {basecamp ? <BasecampAccounts basecamp={basecamp} /> : '...'}
        </div>
      </div>
      <div className="row">
        <div className="col-sm-12">
          <h3>Login</h3>
          <OAuthLogin
            title={basecamp
              ? <span>Switch account</span>
              : 'Login to Basecamp'}
            loginError='' loginUrl={loginUrls.basecamp} />
        </div>
      </div>
    </div>
  );
};
