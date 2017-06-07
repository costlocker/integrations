import React from 'react';

import { ExternalLink } from '../Helpers';

export default function Projects({ allProjects, redirectToRoute }) {
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
              <th>Costlocker project</th>
              <th>Basecamps</th>
            </tr>
          </thead>
          <tbody>
            {projects.map(project => (
              <tr key={project.id}>
                <td>{project.name} <span className="label label-default">{project.client.name}</span></td>
                <td>
                  {project.basecamps.map(basecamp => (
                    <div key={basecamp.id}>
                      <em>
                        {basecamp.account.name} ({basecamp.account.product})
                      </em>
                      &nbsp;&nbsp;
                      <ExternalLink url={basecamp.url} />
                      <button
                        className="btn btn-link" title="Refresh project"
                        onClick={() => redirectToRoute(
                          'sync',
                          { clProject: project.id }
                        )}
                      >
                        <i className="fa fa-refresh"></i>
                      </button>
                      <br />
                    </div>
                  ))}
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
        <button onClick={() => redirectToRoute('sync')} className="btn btn-success">
          Connect new project ({notConnectedProjectsCount})
        </button>
      </div>
    </div>
    }
  </div>;
};
