import React from 'react';
import Loading from '../ui/Loading'
import { Link } from '../ui/Components';
import { WebhookEvents } from './Webhook';

export default function Webhooks({ webhooks }) {
  if (!webhooks) {
    return <Loading title='Loading webhooks' />;
  }
  if (!webhooks.length) {
    return <p className="text-muted">No webhooks</p>;
  }
  return <div>
    <h1>Webhooks</h1>
    <table className="table table-striped table-hover vertical-align">
      <thead>
        <tr>
          <th>Webhook</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        {webhooks.map(w => (
          <tr key={w.uuid}>
            <td>
              {w.url}
              <WebhookEvents webhook={w} />
            </td>
            <td className="text-right">
              <Link title="Recent deliveries" route="webhook.deliveries" params={{ uuid: w.uuid }} className="btn btn-success" />
              <Link title="Example" route="webhook.example" params={{ uuid: w.uuid }} className="btn btn-info" />
              <Link title="Update" route="webhook.update" params={{ uuid: w.uuid }} className="btn btn-warning" />
            </td>
          </tr>
        ))}
      </tbody>
    </table>
  </div>;
}
