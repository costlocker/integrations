import React from 'react';
import { UIView } from 'ui-router-react';

import { User } from '../auth/User';

export default function WizardLayout({ auth, steps }) {
  const stepsItems = [];
  steps.titles.forEach((title, index) => {
    const i = index + 1;
    if (steps.isInvalidStep(i)) {
      stepsItems.push(<li key={i} className="text-muted">{title}</li>);
    } else if (i === steps.getCurrentStep()) {
      stepsItems.push(<li key={i} className="text-primary"><strong>{title}</strong></li>);
    } else {
      stepsItems.push(<li key={i}><a href="" onClick={(e) => steps.goToStep(i, e)}>{title}</a></li>);
    }
  });
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
              <span className="text-warning">Harvest</span><br />
              <span className="text-primary">Costlocker</span>
            </div>
          </div>
          <div className="navbar-text navbar-right text-right">
            <User auth={auth} />
          </div>
        </div>
      </nav>
      <div className="container">
        <div className="row">
          <div className="col-sm-12">
            <ol className="breadcrumb">{stepsItems}</ol>
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
