import React from 'react';
import { Number } from '../Helpers';

const CostlockerCosts = ({ peopleCosts }) => {
  const taskToPeopleCosts = () => {
    const rows = [];
    peopleCosts.tasks.forEach(task => {
      rows.push(
        <tr key={task.id}>
          <th>{task.activity.name}</th>
          <td></td>
          <td><Number value={task.hours.tracked} /></td>
          <td><Number value={task.activity.hourly_rate} /></td>
          <td><Number value={task.finance.revenue} /></td>
        </tr>
      );
      task.people.forEach(person => {
        const key = `${task.id}-${person.id}`
        rows.push(
          <tr key={key}>
            <td>- <em>{person.person.full_name}</em></td>
            <td><Number value={person.hours.budget} /></td>
            <td><Number value={person.hours.tracked} /></td>
            <td></td>
            <td></td>
          </tr>
        );
      })
    });
    return rows;
  }

  return (
    <table className="table table-striped table-hover table-condensed">
      <thead>
        <tr>
          <th>Activity / Person</th>
          <th>Estimated hours</th>
          <th>Tracked hours</th>
          <th>Client rate</th>
          <th>Revenue</th>
        </tr>
      </thead>
      <tbody>
        {taskToPeopleCosts()}
      </tbody>
    </table>
  )
};

const PeopleCosts = ({ peopleCosts, project, fixedBudget }) => {
  return (
    <div>
      <h2>Harvest Project</h2>
      <div className="row">
        <ul>
          <li>
            <a href="http://help.getharvest.com/harvest/projects/setting-up-projects/how-to-add-a-project-invoice-and-budget-methods/#setting-up-a-budget" target="_blank">Budget by</a>
            : <strong>{project.finance.budget_by}</strong>
          </li>
          <li>
            <a href="http://help.getharvest.com/harvest/projects/setting-up-projects/how-to-add-a-project-invoice-and-budget-methods/#setting-up-an-invoice-method" target="_blank">Invoiced by</a>
            : <strong>{project.finance.bill_by}</strong>
          </li>
          {fixedBudget &&
          <li>
            <a href="https://www.getharvest.com/blog/2017/05/introducing-fixed-fee-projects/" target="_blank">Fixed fee</a>
            : <strong>{fixedBudget}</strong>
          </li>
          }
        </ul>
      </div>
      <h2>Tasks, Team &rarr; Activities, People</h2>
      <div className="row">
        <div className="col-sm-5">
          <h3>Harvest tasks</h3>
          <table className="table table-striped table-hover table-condensed">
            <thead>
              <tr>
                <th>Task</th>
                <th>Total hours</th>
              </tr>
            </thead>
            <tbody>
              {peopleCosts.tasks.map(task => (
                <tr key={task.id}>
                  <th>{task.activity.name}</th>
                  <td><Number value={task.hours.tracked} /></td>
                </tr>
              ))}
            </tbody>
          </table>
          <h3>Harvest team</h3>
          <table className="table table-striped table-hover table-condensed">
            <thead>
              <tr>
                <th>Task</th>
                <th>Total hours</th>
                <th>Cost rate</th>
              </tr>
            </thead>
            <tbody>
              {peopleCosts.people.map(person => (
                <tr key={person.id}>
                  <th title={person.person.email}>{person.person.full_name}</th>
                  <td><Number value={person.hours.tracked} /></td>
                  <td><Number value={person.person.salary.hourly_rate} /></td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        <div className="col-sm-7">
          <h3>Personnel costs in Costlocker</h3>
          <CostlockerCosts peopleCosts={peopleCosts} />
        </div>
      </div>
    </div>
  );
}

export { PeopleCosts, CostlockerCosts };
