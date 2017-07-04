import React from 'react';

import { Errors, ExternalLink } from '../ui/Components';

export default function Login({ costlockerAuth, fakturoidAuth, isLoggedInFakturoid, loginUrls, loginError }) {
  const fakturoidUrl = fakturoidAuth
    ? `https://app.fakturoid.cz/${fakturoidAuth.account.slug}`
    : 'https://app.fakturoid.cz';
  return (
    <div>
      <div className="row text-center">
        <div className="col-sm-12">
          <Errors title="Login error" error={loginError} />
        </div>
      </div>
      <div className="row">
        <div className="col-sm-6 text-center">
          <h2>Costlocker <ExternalLink url="https://new.costlocker.com" /></h2>
          <a href={loginUrls.costlocker} className="btn btn-primary">
            {costlockerAuth
              ? <span>Switch account <strong>{costlockerAuth.person.first_name} {costlockerAuth.person.last_name}</strong></span>
              : 'Login to Costlocker'}
          </a>
        </div>
        <div className="col-sm-6">
          <h2>Fakturoid <ExternalLink url={fakturoidUrl} /></h2>
          {costlockerAuth ? (
            <form action={loginUrls.fakturoid} method="POST">
              <div className="form-group">
                <label htmlFor="email">Email address</label>
                <input type="email" className="form-control" id="email" name="email" placeholder="Email"
                  defaultValue={fakturoidAuth ? fakturoidAuth.person.email : null} />
              </div>
              <div className="form-group">
                <label htmlFor="token">API token</label>
                <input type="text" className="form-control" id="token" name="token" />
                <p className="help-block">It's not password! You can find the token in <strong>Já &rarr; API klíč</strong></p>
              </div>
              <div className="form-group">
                <label htmlFor="token">Fakturoid slug (subdomain)</label>
                <input type="text" className="form-control" id="slug" name="slug"
                  defaultValue={fakturoidAuth ? fakturoidAuth.account.slug : null} />
                <p className="help-block">You can see slug in fakturoid url <strong>https://app.fakturoid.cz/YOUR_SLUG/dashboard</strong>.</p>
              </div>
              <button type="submit" className="btn btn-primary btn-block">{isLoggedInFakturoid ? 'Switch account' : 'Login'}</button>
            </form>
          ) : (
              <p className="text-muted">At first you have to login to Costlocker</p>
            )}
        </div>
      </div>
    </div>
  );
};
