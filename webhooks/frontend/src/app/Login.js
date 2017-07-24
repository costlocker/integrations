import React from 'react';

import { ExternalLink, Link } from '../ui/Components';

export default function Login({ costlockerAuth, form, errors, logout }) {
  return (
    <div>
      <div className="row">
        <div className="col-sm-6 col-sm-offset-3">
          <br />
          <div className="alert alert-warning">
            <strong>APP for DEVELOPERS <ExternalLink url="https://costlocker.github.io/" /></strong><br />
            Be aware that this app is designed for API developers.
            You'll see a lot of <code>JSON</code>. If you don't know,
            what is <code>JSON</code>, <code>curl</code>&hellip;, then it can be hard to understand this app.
          </div>

          <h2>Costlocker <ExternalLink url="https://new.costlocker.com" /></h2>
          <form onSubmit={form.submit}>
            {errors}
            <div className="form-group">
              <label htmlFor="token">Personal Access Token</label>
              <input required type="text" className="form-control" id="token" name="token" placeholder="Token"
                value={form.get('token')} onChange={form.set('token')} />
              <p className="help-block">
                Copy token from&nbsp;
                <a href={`${form.get('host')}/api-token`} target="_blank" rel="noopener noreferrer">/api-token</a>
              </p>
            </div>
            <div className="form-group">
              <label htmlFor="host">Costlocker API</label>
              <input required type="text" className="form-control" id="host" name="host"
                value={form.get('host')} onChange={form.set('host')} />
            </div>
            {costlockerAuth ? (
            <div className="row">
              <div className="col-sm-9">
                <button type="submit" className="btn btn-primary btn-block">
                  Switch account
                </button>
              </div>
              <div className="col-sm-3">
                <Link title="Logout" action={logout} className="btn btn-default btn-block" />
              </div>
            </div>
            ) : (
            <button type="submit" className="btn btn-primary btn-block">
              Login
            </button>
            )}
          </form>
        </div>
      </div>
    </div>
  );
};
