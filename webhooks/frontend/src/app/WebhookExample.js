import React from 'react';
import Loading from '../ui/Loading'

export default function WebhookExample({ webhook, example }) {
  if (!example) {
    return <Loading title='Loading webhook example' />;
  }
  return <div>
    <h2>Headers</h2>
    <pre><code>{JSON.stringify(example.headers, null, 2)}</code></pre>
    <h2>Body</h2>
    <pre><code>{JSON.stringify(example.body, null, 2)}</code></pre>
  </div>;
}
