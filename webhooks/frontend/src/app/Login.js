import React from 'react';

import { ExternalLink } from '../ui/Components';

export default function Login({ costlockerAuth, form }) {
  return (
    <div>
      <div className="row">
        <div className="col-sm-12">
          <h2>Costlocker <ExternalLink url="https://new.costlocker.com" /></h2>
          <form onSubmit={form.submit}>
            <div className="form-group">
              <label htmlFor="token">Personal Access Token</label>
              <input required type="text" className="form-control" id="token" name="token" placeholder="Token" />
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
            <button type="submit" className="btn btn-primary btn-block">
              {costlockerAuth ? <span>Switch account</span> : 'Login'}
            </button>
          </form>
        </div>
      </div>
    </div>
  );
};
