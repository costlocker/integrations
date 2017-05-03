import React from 'react';
import { UIView } from 'ui-router-react';

export default function WizardLayout({ user, currentStep, goToStep, stepTitles }) {
  const steps = [];
  stepTitles.forEach((title, index) => {
    const i = index + 1;
    if (i > currentStep) {
      steps.push(<li key={i} className="text-muted">{title}</li>);
    } else if (i === currentStep) {
      steps.push(<li key={i} className="active"><strong>{title}</strong></li>);
    } else {
      steps.push(<li key={i}><a onClick={(e) => goToStep(i, e)}>{title}</a></li>);
    }
  });
  return (
    <div>
      <div className="row bg-info">
        <div className="col-sm-6">
          Harvest -> Costlocker
        </div>
        <div className="col-sm-6 text-right">
          {user}
        </div>
      </div>
      <div className="row">
        <div className="col-sm-12">
          <ol className="breadcrumb">{steps}</ol>
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
