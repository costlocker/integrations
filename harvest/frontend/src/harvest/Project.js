import React from 'react';

const taskToPeopleCosts = (tasks) => {
  const rows = [];
  tasks.forEach(task => {
    rows.push(
      <tr key={task.id}>
        <th>{task.name}</th>
        <td></td>
        <td>{task.total_hours}</td>
        <td>{task.billed_rate}</td>
      </tr>
    );
    task.people.forEach(person => {
      const key = `${task.id}-${person.id}`
      rows.push(
        <tr key={key}>
          <td>- <em>{person.user_name}</em></td>
          <td>{person.projected_hours}</td>
          <td>{person.total_hours}</td>
          <td>-</td>
        </tr>
      );
    })
  });
  return rows;
}

export default function Project({ project, peopleCosts }) {
  const tasks = peopleCosts ? peopleCosts.tasks : [];
  const people = peopleCosts ? peopleCosts.people : [];
  return (
    <div>
      <ul>
        <li>ID: <strong>{project.id}</strong></li>
        <li>Name: <strong>{project.name}</strong></li>
        <li>Client: <strong>{project.client.name}</strong></li>
        <li>Dates: <strong>{project.dates.date_start}</strong> - <strong>{project.dates.date_end}</strong></li>
      </ul>
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
              {tasks.map(task => (
                <tr key={task.id}>
                  <th>{task.name}</th>
                  <td>{task.total_hours}</td>
                  <td>{task.billed_rate}</td>
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
              </tr>
            </thead>
            <tbody>
              {people.map(person => (
                <tr key={person.id}>
                  <th>{person.user_name}</th>
                  <td>{person.total_hours}</td>
                  <td>{person.billed_rate}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        <div className="col-sm-6">
          <h3>People costs in Costlocker</h3>
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
              {taskToPeopleCosts(tasks)}
            </tbody>
          </table>
        </div>
      </div>
      <hr />
      <h2>Debug</h2>
      <pre>{JSON.stringify([project, peopleCosts], null, 2)}</pre>
    </div>
  );
};
