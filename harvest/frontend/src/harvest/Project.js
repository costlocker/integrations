import React from 'react';

export default function Project({ project, data, detailComponent, steps }) {
  let detail = <div>Loading...</div>;
  let navigation = null;
  if (data !== null) {
    if (steps.getCurrentTitle() !== 'Summary') {
      navigation =
        <div className="breadcrumb">
          <div className="row">
            <div className="col-sm-6 text-left">
              <button className="btn btn-default btn-sm" onClick={steps.goToPreviousStep}>&larr; Back to {steps.getPreviousTitle()}</button>
            </div>
            <div className="col-sm-6 text-right">
              <button className="btn btn-info btn-sm" onClick={steps.goToNextStep}>Continue to {steps.getNextTitle()} &rarr;</button>
            </div>
          </div>
        </div>
    };
    detail = <div>{detailComponent}</div>;
  }
  return (
    <div>
      {navigation}
      <h1 title={project.id}>
        {project.name}
        <span className="label label-primary" title="Client">{project.client.name}</span>
        <span className="label label-warning" title="Dates">{project.dates.date_start} - {project.dates.date_end}</span>
        <a href={project.links.harvest} target="_blank" className="btn btn-link btn-sm">Go to harvest</a>
      </h1>
      {detail}
      {navigation}
    </div>
  );
};
