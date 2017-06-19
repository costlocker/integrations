import React from 'react';

import { ExternalLink, Button } from '../ui/Components';

export default function Projects({ allProjects, disconnect }) {
  if (!allProjects) {
    return <span>Loading....</span>;
  }
  const projects = allProjects.filter(p => p.basecamps.length);
  const notConnectedProjectsCount = allProjects.length - projects.length;

  return <div>
    <div className="row">
      <div className="col-sm-12">
        <h1>Projects</h1>
      </div>
    </div>
    <div className="row">
      <div className="col-sm-12">
        {projects.length ? (
        <table className="table table-striped table-hover table-condensed">
          <thead>
            <tr>
              <th>Costlocker</th>
              <th>Basecamp account</th>
              <th>Synchronization</th>
              <th width='200' className="text-center">Actions</th>
            </tr>
          </thead>
          <tbody>
            {projects.map(project => (
              <tr key={project.id}>
                <td>{project.name} <span className="label label-default">{project.client.name}</span></td>
                <td>
                  {project.basecamps.map(basecamp => (
                    <div key={basecamp.id}>
                      <em>{basecamp.account.name}</em>&nbsp;&nbsp; <ExternalLink url={basecamp.url} />
                      <br />
                      {basecamp.account.product}, {basecamp.account.identity.email_address}
                    </div>
                  ))}
                </td>
                <td>
                  {project.basecamps.map(basecamp => (
                    <div>
                      {basecamp.settings.areTodosEnabled ? <span className="text-primary">Costlocker &rarr; Basecamp</span> : ''}<br />
                      {basecamp.settings.areTasksEnabled ? <span className="text-success">Basecamp &rarr; Costlocker</span> : ''}
                    </div>
                  ))}
                </td>
                <td className="text-right">
                  <Button route='sync' params={{ clProject: project.id }}
                    title={<span><i className="fa fa-refresh"></i> Refresh</span>} className="btn btn-sm btn-primary" />
                  &nbsp;&nbsp;
                  <Button action={() => disconnect(project.id)}
                    title={<span><i className="fa fa-trash"></i> Disconnect</span>} className="btn btn-sm btn-danger" />
                </td>
              </tr>
            ))}
          </tbody>
        </table>
        ) : (
        <p className="text-muted">No project in connected to Basecamp</p>
        )}
      </div>
    </div>
    {notConnectedProjectsCount &&
    <div className="row">
      <div className="col-sm-12">
        <Button route='sync' title={<span>Connect new project ({notConnectedProjectsCount})</span>} className="btn btn-success" />
      </div>
    </div>
    }
  </div>;
};
