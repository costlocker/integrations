import React from 'react';

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
        <a className="btn btn-sm btn-info" href="#" onClick={() => refresh()}>Refresh</a>
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
              let cssClass = null;
              if (event.status === 'success') {
                cssClass = 'success';
              } else if (event.status === 'failure') {
                cssClass = 'danger';
              }
              return <tr key={event.id} className={cssClass}>
                <td>{event.date}</td>
                <td>{event.description}</td>
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
