import React from 'react';

const ProjectsList = ({ title, projects, goTo }) => {
  if (!projects.length) {
    return null;
  }
  const areNew = title === 'New projects' ;
  const status = areNew ? 'new' : 'imported';
  const labelClass = areNew ? 'primary' : 'success';
  return (
    <div>
      <ul title={title}>
        {projects.map(project => (
          <li key={project.id}>
            <a href="" onClick={(e) => goTo(e, project)}>
              {project.name}
            </a> <span className={`label label-${labelClass}`}>{status}</span>
          </li>
        ))}
      </ul>
    </div>
  )
};

export default function Projects({ projects, goToProject }) {
  const goTo = (e, project) => {
    e.preventDefault();
    goToProject(project);
  };

  if (!projects) {
    return <span>Loading....</span>;
  }

  return <div>
    <h2>Projects</h2>
    <ProjectsList title="New projects" projects={projects.new} goTo={goTo} />
    <ProjectsList title="Imported projects" projects={projects.imported} goTo={goTo} />
  </div>;
};
