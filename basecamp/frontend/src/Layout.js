import React from 'react';
import { UIView } from 'ui-router-react';
import { appState } from './state';
import { redirectToRoute } from './Router';
import { User } from './auth/User';

const Navigation = () => {
  const goTo = (route) => (e) => {
    e.preventDefault();
    redirectToRoute(route);
  };
  return (
    <ul className="nav nav-pills">
      <li><a href="" onClick={goTo('projects')}>Costlocker projects</a></li>
      <li><a href="" onClick={goTo('basecamp')}>Basecamp accounts</a></li>
      <li><a href="" onClick={goTo('sync')}>Synchronize</a></li>
      <li><a href="" onClick={goTo('events')}>Events</a></li>
      <li><a href="" onClick={goTo('settings')}>Settings</a></li>
    </ul>
  );
};

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
            {auth.costlocker ? <Navigation /> : ''}
          </div>
        </div>
        <div className="row">
          <div className="col-sm-12">
            <UIView />
          </div>
        </div>
      </div>
    </div>
  );
};
