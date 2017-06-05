import React from 'react';

import { ExternalLink } from '../Helpers';

export default function Projects({ projects, redirectToRoute }) {
  if (!projects) {
    return <span>Loading....</span>;
  }

  return <div>
    <div className="row">
      <div className="col-sm-6">
        <h1>Projects <span className="badge bg-primary">{projects.length}</span></h1>
      </div>
      <div className="col-sm-6 text-right">
        <button onClick={() => redirectToRoute('sync')} className="btn btn-success">
          Connect Costlocker and Basecamp
        </button>
      </div>
    </div>
    <div className="row">
      <div className="col-sm-12">
        <table className="table table-striped table-hover table-condensed">
          <thead>
            <tr>
              <th>Costlocker project</th>
              <th>Is synchronized?</th>
              <th>Basecamps</th>
            </tr>
          </thead>
          <tbody>
            {projects.map(project => (
              <tr key={project.id}>
                <td>{project.name} <span className="label label-default">{project.client.name}</span></td>
                <td>{
                  project.basecamps.length
                  ? <span className="label label-success">YES</span>
                  : <span className="label label-danger">NO</span>
                }
                </td>
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
                          { account: basecamp.account.id, clProject: project.id, bcProject: basecamp.id }
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
      </div>
    </div>
  </div>;
};
