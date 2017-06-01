import React from 'react';
import { UIView } from 'ui-router-react';

export default function Layout() {
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
              <span className="text-warning">Basecamp</span><br />
              <span className="text-primary">Costlocker</span>
            </div>
          </div>
          <div className="navbar-text navbar-right text-right">
            USER
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
