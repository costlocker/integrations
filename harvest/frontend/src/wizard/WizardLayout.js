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
      stepsItems.push(<li key={i} className="active"><strong>{title}</strong></li>);
    } else {
      stepsItems.push(<li key={i}><a href="" onClick={(e) => steps.goToStep(i, e)}>{title}</a></li>);
    }
  });
  return (
    <div>
      <div className="row bg-info">
        <div className="col-sm-6">
          Harvest<br />
          Costlocker
        </div>
        <div className="col-sm-6 text-right">
          <User auth={auth} isFirstStep={steps.getCurrentStep() === 1} />
        </div>
      </div>
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
  );
};
