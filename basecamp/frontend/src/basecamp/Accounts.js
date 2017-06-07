import React from 'react';

import OAuthLogin from '../auth/OAuthLogin';
import { ExternalLink } from '../Helpers';

export default function Login({ basecampUser, costlockerUser, loginUrls, users }) {
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
          <table className="table table-striped table-condensed">
            <thead>
              <tr>
                <th>Costlocker</th>
                <th colSpan="3" className="active text-center">Basecamp</th>
              </tr>
              <tr>
                <th>Person</th>
                <th>Person</th>
                <th>Basecamp</th>
                <th>Version</th>
              </tr>
            </thead>
            <tbody>
            {users.map(user => user.accounts.map(account => {
              const isCurrentUser = user.person.email === costlockerUser.person.email;
              const labelCss = isCurrentUser ? 'label label-info' : 'label label-default';
              return <tr key={account.id}>
                {isCurrentUser ? (
                <th>
                  {user.person.first_name} {user.person.last_name}&nbsp;
                  <span className={labelCss}>{user.person.email}</span>
                </th>
                ) : (
                <td>
                  {user.person.first_name} {user.person.last_name}&nbsp;
                  <span className={labelCss}>{user.person.email}</span>
                </td>
                )}
                <td>
                  {account.identity.first_name} {account.identity.last_name}&nbsp;
                  <span className={labelCss}>{account.identity.email_address}</span>
                </td>
                <td>{account.name} <ExternalLink url={account.urlApp} /></td>
                <td>{account.product}</td>
              </tr>;
            }))}
            </tbody>
          </table>
        </div>
      </div>
      <div className="row">
        <div className="col-sm-12">
          <OAuthLogin
            title={basecampUser
              ? <span>Connect another Basecamp account</span>
              : 'Login to Basecamp'}
            loginError='' loginUrl={loginUrls.basecamp} />
        </div>
      </div>
    </div>
  );
};
