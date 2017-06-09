import React from 'react';

import OAuthLogin from './OAuthLogin';
import { ExternalLink, Button } from '../ui/Components';

export default function Login({ basecampUser, costlockerUser, loginUrls, loginError, users, disconnect }) {
  return (
    <div>
      <div className="row">
        <div className="col-sm-12">
          <h1>Accounts</h1>
        </div>
      </div>
      <div className="row">
        <div className="col-sm-12">
          <h3>Costlocker</h3>
          <OAuthLogin
            title={costlockerUser
              ? <span>Switch account <strong>{costlockerUser.person.first_name} {costlockerUser.person.last_name}</strong></span>
              : 'Login to Costlocker'}
            loginError={null} loginUrl={loginUrls.costlocker} />
        </div>
      </div>
      <div className="row">
        <div className="col-sm-12">
          <h3>Basecamp</h3>
          <p><strong>Supported products:</strong> Basecamp 3, Basecamp 2, and Basecamp Classic</p>
        </div>
      </div>
      <div className="row">
        <div className="col-sm-12">
          <h4>Connected Accounts</h4>
          {users.length ? (
          <table className="table table-striped table-condensed">
            <thead>
              <tr>
                <th>Costlocker</th>
                <th colSpan="4" className="active text-center">Basecamp</th>
              </tr>
              <tr>
                <th>Person</th>
                <th>Person</th>
                <th>Basecamp</th>
                <th>Version</th>
                <th></th>
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
                <td>{isCurrentUser ? <Button action={() => disconnect(account.id)} title='Disconnect' className="btn btn-sm btn-danger" /> : ''}</td>
              </tr>;
            }))}
            </tbody>
          </table>
          ) : (
          <p className="text-muted">No Basecamp account is connected in your company</p>
          )}
        </div>
      </div>
      <div className="row">
        <div className="col-sm-12">
          <OAuthLogin
            title={basecampUser
              ? <span>Connect another Basecamp account</span>
              : 'Login to Basecamp'}
            loginError={loginError} loginUrl={loginUrls.basecamp} />
        </div>
      </div>
    </div>
  );
};
