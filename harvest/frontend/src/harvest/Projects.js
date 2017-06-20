import React from 'react';
import { ExternalLink } from '../Helpers';

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
          <th className="text-center" width='120'>Actions</th>
        </tr>
      </thead>
      <tbody>
      {projects.map(project => {
        const isNewProject = project.status === 'new';
        const status = isNewProject ? 'new' : 'imported';
        const labelClass = isNewProject ? 'primary' : 'success';
        const isFixed = isFixedProject(project);
        const goToProject = (e) => {
          let fixedBudget = null;
          if (isFixed) {
            fixedBudget = parseInt(prompt("Please insert fixed fee"), 0);
            if (isNaN(fixedBudget)) {
              e.preventDefault();
              return;
            }
          }
          return goTo(e, project, fixedBudget);
        };

        const cells = [
          <th key='project' className='text-left'>{project.name}</th>,
          <td key='status'><span className={`label label-${labelClass}`}>{status}</span></td>,
          <td key='billable'>{project.finance.billable
            ? <span className="fa fa-check text-success"></span>
            : <span className="fa fa-close text-danger"></span>}</td>,
        ];
        if (isFixed) {
          cells.push(<td key='fixed' colSpan='2'>Fixed fee project</td>);
        } else {
          cells.push(<td key='bill_by'>{project.finance.bill_by}</td>);
          cells.push(<td key='budget_by'>{project.finance.budget_by}</td>);
        }
        cells.push(
          <td key='actions' className="btn-toolbar">
            <a href="" onClick={goToProject} className={`btn btn-${labelClass} btn-sm`}>Import</a>
            <ExternalLink url={project.links.harvest} className="btn btn-default btn-sm" />
          </td>
        );
        return <tr key={project.id}>{cells}</tr>
      })}
      </tbody>
    </table>
  )
};

export default function Projects({ projects, goToProject }) {
  const goTo = (e, project, fixedBudget) => {
    e.preventDefault();
    goToProject(project, fixedBudget);
  };

  if (!projects) {
    return <span>Loading....</span>;
  }

  return <div>
    <h2>Projects</h2>
    <ProjectsList projects={projects} goTo={goTo} />
  </div>;
};
