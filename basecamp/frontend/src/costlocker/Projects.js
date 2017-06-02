import React from 'react';

export default function Projects({ projects }) {
  if (!projects) {
    return <span>Loading....</span>;
  }

  return <div>
    <h1>Projects <span className="badge bg-primary">{projects.length}</span></h1>
    <table className="table table-striped table-hover table-condensed">
      <thead>
        <tr>
          <th>Costlocker project</th>
          <th>Is synchronized?</th>
          <th>Basecamp</th>
        </tr>
      </thead>
      <tbody>
        {projects.map(project => (
          <tr key={project.id}>
            <td>{project.name} <span className="label label-default">{project.client.name}</span></td>
            <td>{
              project.id % 2 === 1
              ? <span className="label label-success">YES</span>
              : <span className="label label-danger">NO</span>
            }
            </td>
            <td><button className="btn btn-sm btn-disabled">Synchronize in basecamp</button></td>
          </tr>
        ))}
      </tbody>
    </table>
  </div>;
};
