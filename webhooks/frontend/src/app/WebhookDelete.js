import React from 'react';

export default function WebhookDelete({ deleteWebhook, errors }) {
  return <div>
    {errors}
    <button className="btn btn-danger" onClick={deleteWebhook}>Delete the webhook</button>
  </div>;
}
