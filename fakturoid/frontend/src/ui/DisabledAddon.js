import React from 'react';
import { appHost } from '../config';

export default function DisabledAddon() {
  return (
    <div className="container text-center">
      <div className="row">
        <div className="col-sm-6 col-sm-offset-3">
          <div className="panel panel-danger error-page">
            <div className="panel-heading">
              <h1 className="panel-title">Fakturoid integration is disabled</h1>
            </div>
            <div className="panel-body">
              Ask your owner to <a href={`${appHost}/settings/addons`}>enable the addon in Settings</a>.
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
