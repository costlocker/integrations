import React from 'react';
import { UIView } from 'ui-router-react';

export default function WizardLayout({ user, currentStep, goToStep }) {
  const steps = [];
  for (let i = 1; i <= currentStep; i++) {
    if (i === currentStep) {
      steps.push(<li key={i} className="active">Step #{i}</li>);
    } else {
      steps.push(<li key={i}><a onClick={(e) => goToStep(i, e)}>Step #{i}</a></li>);
    }
  }
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
