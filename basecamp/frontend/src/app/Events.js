import React from 'react';

import { Button, Link, ExternalLink } from '../ui/Components';
import { Logo } from '../ui/Images';
import Loading from '../ui/Loading';

export default function Events({ events, refresh }) {
  if (!events) {
    return <Loading title="Loading events" />;
  }

  const rows = [];
  events.forEach(event => {
    const statusToCssClass = {
      'success': 'success',
      'failure': 'danger',
      'nochange': 'warning',
      'partial': 'info',
    };
    rows.push(
      <tr key={event.id} className={statusToCssClass[event.status]}>
        <td>{event.date}</td>
        <th>
          {event.user
            ? <div title={event.user.person.email}>{event.user.person.first_name} {event.user.person.last_name}</div>
            : ''
          }
        </th>
        <td>{event.description}</td>
        <td>
          {event.links ? (
            <div>
              <ExternalLink url={event.links.costlocker} className="text-primary first"
                title={<span><Logo app="costlocker" color="blue" /> Open project</span>} />
              <ExternalLink url={event.links.basecamp} className="text-success"
                title={<span><Logo app="basecamp" /> Open project</span>} />
            </div>
          ) : null}
        </td>
      </tr>
    );
    if (!event.changelogs.length && !event.errors.length) {
      return;
    }
    rows.push(
      <tr key={`${event.id}-changelog`} className={statusToCssClass[event.status]}>
        <td colSpan="4">
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
          {event.errors.length ? <span className="text-muted">{event.errors.join('<br />')}</span> : ''}
        </td>
      </tr>
    );
  });

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
        <table className="table table-striped table-hover table-condensed table-valign">
          <thead>
            <tr>
              <th>Date</th>
              <th>User</th>
              <th>Event</th>
              <th>Links</th>
            </tr>
          </thead>
          <tbody>
            {rows}
          </tbody>
        </table>
      </div>
    </div>
  </div>;
};
