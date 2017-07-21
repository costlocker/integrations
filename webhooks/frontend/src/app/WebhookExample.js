import React from 'react';
import Loading from '../ui/Loading'

export default function WebhookExample({ webhook, example }) {
  if (!example) {
    return <Loading title='Loading webhook example' />;
  }
  return <div>
    {webhook &&
    <div>
      <h1>{webhook.url}</h1>
      <p>{webhook.events.join(', ')}</p>
    </div>
    }
    <h2>Header</h2>
    <pre><code>{JSON.stringify(example.headers, null, 2)}</code></pre>
    <h2>Body</h2>
    <pre><code>{JSON.stringify(example.body, null, 2)}</code></pre>
  </div>;
}
