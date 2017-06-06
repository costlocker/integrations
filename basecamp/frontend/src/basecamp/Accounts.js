import React from 'react';

import OAuthLogin from '../auth/OAuthLogin';
import { ExternalLink } from '../Helpers';

const BasecampAccounts = ({ accounts }) => {
  return (
    <table className="table table-striped table-hover table-condensed">
      <thead>
        <tr>
          <th>Basecamp</th>
          <th>Version</th>
        </tr>
      </thead>
      <tbody>
        {accounts.map(account => (
          <tr key={account.id}>
            <td>{account.name} <ExternalLink url={account.app_href} /></td>
            <td>{account.product}</td>
          </tr>
        ))}
      </tbody>
    </table>
  )
}

export default function Login({ basecamp, loginUrls, availableAccounts }) {
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
          <h3>Connected Accounts</h3>
          <BasecampAccounts accounts={availableAccounts} />
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
