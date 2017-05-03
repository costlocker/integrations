import React from 'react';

export default function Project({ project, data, detailComponent, steps }) {
  let detail = <div>Loading...</div>;
  if (data !== null) {
    let navigation = null;
    if (steps.getCurrentTitle() !== 'Summary') {
      navigation = <div className="row">
        <div className="col-sm-6 text-left">
          <button className="btn btn-default" onClick={steps.goToPreviousStep}>Back to {steps.getPreviousTitle()}</button>
        </div>
        <div className="col-sm-6 text-right">
          <button className="btn btn-success" onClick={steps.goToNextStep}>Continue to {steps.getNextTitle()}</button>
        </div>
      </div>
    };
    detail = <div>
      {navigation}
      {detailComponent}
      {navigation}
    </div>;
  }
  return (
    <div>
      <h1>
        {project.name}
        <span className="label label-primary" title="Client">{project.client.name}</span>
        <span className="label label-info" title="Dates">{project.dates.date_start} - {project.dates.date_end}</span>
        <span className="label label-default" title="ID">{project.id}</span>
      </h1>
      {detail}
    </div>
  );
};
