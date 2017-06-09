import React from 'react';

import { Button } from '../ui/Components';

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
              <th>Date</th>
              <th>Event</th>
              <th>Author</th>
            </tr>
          </thead>
          <tbody>
            {events.map(event => {
              const statusToCssClass = {
                'success': 'success',
                'failure': 'danger',
                'nochange': 'warning',
              };
              return <tr key={event.id} className={statusToCssClass[event.status]}>
                <td>{event.date}</td>
                <td>
                  {event.description}<br />
                  {event.error ? <span className="text-muted">{event.error}</span> : ''}
                </td>
                <td>{event.user
                  ? <div>
                      <strong>{event.user.person.first_name} {event.user.person.last_name} </strong>
                      <span className="label label-default">${event.user.person.email}</span>
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
