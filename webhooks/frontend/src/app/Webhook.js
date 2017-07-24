import React from 'react';
import { PageWithSubpages } from '../ui/App';

export function WebhookEvents({ webhook }) {
  return <div>
    {webhook.events.map((event) => (
      <span key={event}>
        <span className="label label-primary">{event}</span>
        &nbsp;
      </span>
    ))}
  </div>;
};

export function Webhook({ webhook }) {
  return <PageWithSubpages
    pages={[
      {
        name: 'Recent deliveries',
        route: 'webhook.deliveries',
        params: { uuid: webhook.uuid },
      },
      {
        name: 'Example',
        route: 'webhook.example',
        params: { uuid: webhook.uuid },
      },
      {
        name: 'Update',
        route: 'webhook.update',
        params: { uuid: webhook.uuid },
      },
      {
        name: 'Delete',
        route: 'webhook.delete',
        params: { uuid: webhook.uuid },
      },
    ]}
    content={(view) => (
      <div>
        <div>
          <h1>
            {webhook.url}
          </h1>
          <WebhookEvents webhook={webhook} />
        </div>
        <hr />
        {view}
      </div>
    )}
  />;
}
