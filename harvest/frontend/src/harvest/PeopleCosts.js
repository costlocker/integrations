import React from 'react';

const CostlockerCosts = ({ peopleCosts }) => {
  const taskToPeopleCosts = () => {
    const rows = [];
    peopleCosts.tasks.forEach(task => {
      rows.push(
        <tr key={task.id}>
          <th>{task.activity.name}</th>
          <td></td>
          <td>{task.hours.tracked}</td>
          <td>{task.activity.hourly_rate}</td>
        </tr>
      );
      task.people.forEach(person => {
        const key = `${task.id}-${person.id}`
        rows.push(
          <tr key={key}>
            <td>- <em>{person.person.full_name}</em></td>
            <td>{person.hours.budget}</td>
            <td>{person.hours.tracked}</td>
            <td>-</td>
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
        </tr>
      </thead>
      <tbody>
        {taskToPeopleCosts()}
      </tbody>
    </table>
  )
};

const PeopleCosts = ({ peopleCosts, project }) => {
  return (
    <div>
      <h2>Harvest Project</h2>
      <div className="row">
        <ul>
          <li>Invoiced by: <strong>{project.finance.bill_by}</strong></li>
          <li>Budget by: <strong>{project.finance.budget_by}</strong></li>
        </ul>
      </div>
      <h2>Tasks, Team &rarr; Activities, People</h2>
      <div className="row">
        <div className="col-sm-6">
          <h3>Harvest tasks</h3>
          <table className="table table-striped table-hover table-condensed">
            <thead>
              <tr>
                <th>Task</th>
                <th>Total hours</th>
                <th>Billed rate</th>
              </tr>
            </thead>
            <tbody>
              {peopleCosts.tasks.map(task => (
                <tr key={task.id}>
                  <th>{task.activity.name}</th>
                  <td>{task.hours.tracked}</td>
                  <td>{task.activity.hourly_rate}</td>
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
                <th>Billed rate</th>
                <th>Cost rate</th>
              </tr>
            </thead>
            <tbody>
              {peopleCosts.people.map(person => (
                <tr key={person.id}>
                  <th title={person.person.email}>{person.person.full_name}</th>
                  <td>{person.hours.tracked}</td>
                  <td>{person.finance.billed_rate}</td>
                  <td>{person.person.salary.hourly_rate}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        <div className="col-sm-6">
          <h3>People costs in Costlocker</h3>
          <CostlockerCosts peopleCosts={peopleCosts} />
        </div>
      </div>
    </div>
  );
}

export { PeopleCosts, CostlockerCosts };
