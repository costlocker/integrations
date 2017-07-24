import React from 'react';

export default function WebhookDelete({ deleteWebhook, errors }) {
  return <div>
    {errors && errors.length &&
    <div className="row">
      <div className="col-sm-12">
        <div className="alert alert-danger">
          <strong>Please fix following errors:</strong><br />
          <pre><code>{JSON.stringify(errors, null, 2)}</code></pre>
        </div>
      </div>
    </div>
    }
    <button className="btn btn-danger" onClick={deleteWebhook}>Delete the webhook</button>
  </div>;
}
