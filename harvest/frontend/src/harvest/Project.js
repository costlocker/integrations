import React from 'react';

export default function Project({ project, data, detailComponent, goToNextStep }) {
  let detail = <div>Loading...</div>
  const nextStep = <button className="btn btn-success" onClick={goToNextStep}>Continue to next step</button>;
  if (data !== null) {
    detail = <div>
      {nextStep}
      {detailComponent}
      {nextStep}
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
