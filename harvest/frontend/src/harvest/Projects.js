import React from 'react';

export default function Projects({ projects, goToProject }) {
  const goTo = (e, project) => {
    e.preventDefault();
    goToProject(project);
  };

  if (!projects.length) {
    return <span>Loading....</span>;
  }

  return <div>
    <h2>Projects</h2>
    <ul>
      {projects.map(project => (
        <li key={project.id}><a href="" onClick={(e) => goTo(e, project)}>{project.name}</a></li>
      ))}
    </ul>
  </div>;
};
