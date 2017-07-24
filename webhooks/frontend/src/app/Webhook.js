import React from 'react';
import { PageWithSubpages } from '../ui/App';

export default function Webhook({ webhook }) {
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
    ]}
    content={(view) => (
      <div>
        <div>
          <h1>
            {webhook.url}
          </h1>
          {webhook.events.map((event) => (
            <span key={event} className="label label-default">{event}</span>
          ))}
        </div>
        {view}
      </div>
    )}
  />;
}
