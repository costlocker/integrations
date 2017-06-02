import React from 'react';
import { UIView } from 'ui-router-react';
import { appState } from './state';
import { redirectToRoute } from './Router';
import { User } from './auth/User';

export default function Layout() {
  const auth = appState.cursor(['auth']).deref().toJS();
  return (
    <div>
      <nav className="navbar navbar-default">
        <div className="container">
          <div className="navbar-header">
            <div className="navbar-brand">
              <a href="/">
                <img alt="" src="https://cdn-images-1.medium.com/max/1200/1*BLdn5GGWwijxJkcr0I0rgg.png" width="40px" />
              </a>
            </div>
            <div className="navbar-brand">
              <span className="text-primary">Costlocker</span><br />
              <span className="text-success">Basecamp</span>
            </div>
          </div>
          <div className="navbar-text navbar-right text-right">
            <User auth={auth} redirectToRoute={redirectToRoute} />
          </div>
        </div>
      </nav>
      <div className="container">
        <div className="row">
          <div className="col-sm-12">
            <UIView />
          </div>
        </div>
      </div>
    </div>
  );
};
