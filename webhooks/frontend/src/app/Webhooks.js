import React from 'react';
import Loading from '../ui/Loading'

export default function Webhooks({ webhooks }) {
  if (!webhooks) {
    return <Loading title='Loading webhooks' />;
  }
  if (!webhooks.length) {
    return <p className="text-muted">No webhooks</p>;
  }
  return <table className="table table-striped table-hover">
    <thead>
      <tr>
        <th>URL</th>
        <th>Events</th>
      </tr>
    </thead>
    <tbody>
      {webhooks.map(w => (
        <tr key={w.uuid}>
          <td>{w.url}</td>
          <td>{w.events.join(', ')}</td>
        </tr>
      ))}
    </tbody>
  </table>;
}
