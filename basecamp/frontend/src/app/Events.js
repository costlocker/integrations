import React from 'react';

import { Button, Link } from '../ui/Components';

export default function Projects({ events, refresh }) {
  if (!events) {
    return <span>Loading....</span>;
  }

  return <div>
    <div className="row">
      <div className="col-sm-8">
        <h1>Events</h1>
      </div>
      <div className="col-sm-4 text-right">
        <Button action={refresh} title='Refresh' className="btn btn-sm btn-info" />
      </div>
    </div>
    <div className="row">
      <div className="col-sm-12">
        <table className="table table-striped table-hover table-condensed">
          <thead>
            <tr>
              <th width='100' className="text-center">Date</th>
              <th>Event</th>
              <th className="text-center">Author</th>
            </tr>
          </thead>
          <tbody>
            {events.map(event => {
              const statusToCssClass = {
                'success': 'success',
                'failure': 'danger',
                'nochange': 'warning',
                'partial': 'info',
              };
              return <tr key={event.id} className={statusToCssClass[event.status]}>
                <td className="text-center">{event.date}</td>
                <td>
                  {event.description}
                  {event.project.costlocker ? <span> - <Link title='go to project' route='sync' params={{clProject: event.project.costlocker}} /></span> : ''}
                  <br />
                  {event.changelogs.length ? (
                  <table className="table table-condensed table-inline">
                    <thead>
                      <tr>
                        <th rowSpan="2">System</th>
                        <th colSpan="2">Project</th>
                        <th colSpan="2">Todolists/activities</th>
                        <th colSpan="2">Todos/tasks</th>
                      </tr>
                      <tr>
                        <th>Project ID</th>
                        <th>Is created?</th>
                        <th>Created</th>
                        <th>Deleted</th>
                        <th>Created</th>
                        <th>Deleted</th>
                      </tr>
                    </thead>
                    <tbody>
                      {event.changelogs.map((changelog, index) => {
                        const key = `${event.id}-${index}`;
                        return <tr key={key}>
                          <th>{changelog.system}</th>
                          <td>{changelog.project.id}</td>
                          <td>{changelog.project.createdCount ? '✓' : '-'}</td>
                          <td>{changelog.activities.createdCount}</td>
                          <td>{changelog.activities.deletedCount}</td>
                          <td>{changelog.tasks.createdCount}</td>
                          <td>{changelog.tasks.deletedCount}</td>
                        </tr>
                      })}
                    </tbody>
                  </table>
                  ) : null}
                  {event.errors.length ? <span className="text-muted">{event.errors.join('<br />')}</span> : ''}<br />
                </td>
                <td className="text-center">{event.user
                  ? <div>
                      <strong>{event.user.person.first_name} {event.user.person.last_name} </strong><br />
                      <span className="label label-default">{event.user.person.email}</span>
                    </div>
                  : '-'}
                </td>
              </tr>;
            })}
          </tbody>
        </table>
      </div>
    </div>
  </div>;
};
