import React from 'react';

export default function Projects({ costlockerProjects, basecampProjects, redirectToRoute }) {
  if (!costlockerProjects) {
    return <span>Loading...</span>;
  } else if (!costlockerProjects.length) {
    return <span>No projects available</span>;
  }

  return <div>
    <h1>Synchronize Costlocker & Basecamp</h1>
    <form className="form">
      <div className="form-group">
        <label htmlFor="costlockerProject">Costlocker project</label>
        <select className="form-control" name="costlockerProject" id="costlockerProject">
          {costlockerProjects.map(project => (
            <option key={project.id} value={project.id}>
              {project.name} ({project.client.name})
            </option>
          ))}
        </select>
      </div>
      <div className="form-group">
        <label>How would you like to add this project to the Basecamp</label>
        <div className="radio">
          <label>
            <input type="radio" name="basecampImport" id="basecampImport_create" value="create" defaultChecked />
            Create a new project in Basecamp
          </label>
        </div>
        {basecampProjects.length &&
        <div className="radio">
          <label>
            <input type="radio" name="basecampImport" id="basecampImport_add" value="add" />
            Add to an existing project in Basecamp
          </label>
        </div>
        }
      </div>
      {basecampProjects.length &&
      <div className="form-group">
        <label htmlFor="basecampProject">Basecamp project</label>
        <select className="form-control" name="basecampProject" id="basecampProject">
          {basecampProjects.map(project => (
            <option key={project.id} value={project.id}>
              {project.name}
            </option>
          ))}
        </select>
      </div>
      }
      <button type="submit" className="btn btn-primary">Synchronize</button>
    </form>
  </div>;
};
