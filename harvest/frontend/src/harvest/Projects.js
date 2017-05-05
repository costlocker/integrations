import React from 'react';

const ProjectsList = ({ title, projects, goTo }) => {
  console.log(projects);
  if (!projects.length) {
    return null;
  }
  return (
    <div>
      <h3>{title}</h3>
      <ul>
        {projects.map(project => (
          <li key={project.id}><a href="" onClick={(e) => goTo(e, project)}>{project.name}</a></li>
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
