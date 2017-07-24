import React from 'react';
import Loading from '../ui/Loading'

export default function WebhookDeliveries({ webhook, detail }) {
  if (!detail) {
    return <Loading title='Loading webhook deliveries' />;
  }
  return <div>
    <pre><code>{JSON.stringify(detail.data.recent_deliveries, null, 2)}</code></pre>
  </div>;
}
