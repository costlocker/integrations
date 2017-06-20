import React from 'react';

const isFixedProject = project =>
  project.finance.billable &&
  project.finance.bill_by === 'none' &&
  project.finance.budget_by === 'none';

const ProjectsList = ({ projects, goTo }) => {
  if (!projects.length) {
    return null;
  }
  return (
    <table className="table table-condensed table-striped text-center">
      <thead>
        <tr>
          <th className='text-left'>Project</th>
          <th className="text-center">Status</th>
          <th className="text-center">Is billable?</th>
          <th className="text-center">
            <a href="http://help.getharvest.com/harvest/projects/setting-up-projects/how-to-add-a-project-invoice-and-budget-methods" target="_blank">
              Invoiced by
            </a>
          </th>
          <th className="text-center">
            <a href="http://help.getharvest.com/harvest/projects/setting-up-projects/how-to-add-a-project-invoice-and-budget-methods" target="_blank">
              Budgeted by
            </a>
          </th>
        </tr>
      </thead>
      <tbody>
      {projects.map(project => {
        const isNewProject = project.status == 'new';
        const status = isNewProject ? 'new' : 'imported';
        const labelClass = isNewProject ? 'primary' : 'success';
        const cells = [
          <td className='text-left'><a href="" onClick={(e) => goTo(e, project)}>
            {project.name}
          </a></td>,
          <td><span className={`label label-${labelClass}`}>{status}</span></td>,
          <td>{project.finance.billable
            ? <span className="fa fa-check text-success"></span>
            : <span className="fa fa-close text-danger"></span>}</td>,
        ];
        if (isFixedProject(project)) {
          cells.push(<td colSpan='2'>Fixed fee project</td>);
        } else {
          cells.push(<td>{project.finance.bill_by}</td>);
          cells.push(<td>{project.finance.budget_by}</td>);
        }
        return <tr key={project.id}>{cells}</tr>
      })}
      </tbody>
    </table>
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
    <ProjectsList projects={projects} goTo={goTo} />
  </div>;
};
