import React from 'react';

const events = {
  'Time-entries': [
    "timeentries.create",
    "timeentries.update",
  ],
  'Projects': [
    "projects.create",
    "peoplecosts.change",
    "expenses.change",
    "billing.change",
  ],
};

export default function WebhookFrom({ form, errors, updatedWebhook }) {
  return (
    <div>
      <div className="row">
        <div className="col-sm-12">
          {!updatedWebhook &&
          <h1>Create a webhook</h1>
          }
          {errors}
          <form onSubmit={form.submit}>
            <div className="form-group">
              <label htmlFor="url">URL</label>
              <input required type="url" className="form-control" id="url" name="url" placeholder="https://requestb.in/"
                value={form.get('url')} onChange={form.set('url')} />
            </div>
            <div className="form-group">
              <label htmlFor="is_enabled">Is enabled?</label>
              <div className="checkbox">
                <label>
                  <input
                    type="checkbox" id="is_enabled" name="is_enabled"
                    checked={form.get('is_enabled')} onChange={form.set('is_enabled')}
                  /> Yes, deliver events when selected events are triggered
                </label>
              </div>
            </div>
            {Object.keys(events).map(type => (
            <div key={type} className="form-group">
              <label>{type}</label><br />
              {events[type].map(event => (
              <label key={event} className="checkbox-inline">
                <input type="checkbox" value={event}
                  checked={form.get('events').includes(event)} onChange={form.checkEvent} /> {event}
              </label>
              ))}
            </div>
            ))}
            <button type="submit" className="btn btn-primary btn-block">
              {updatedWebhook ? 'Update a webhook' : 'Create a webhook'}
            </button>
          </form>
        </div>
      </div>
    </div>
  );
};
