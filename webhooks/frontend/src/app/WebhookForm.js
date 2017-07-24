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

export default function WebhookFrom({ form, errors }) {
  return (
    <div>
      <div className="row">
        <div className="col-sm-12">
          <h1>Create a webhook</h1>
          {errors}
          <form onSubmit={form.submit}>
            <div className="form-group">
              <label htmlFor="url">URL</label>
              <input required type="url" className="form-control" id="url" name="url" placeholder="https://requestb.in/"
                value={form.get('url')} onChange={form.set('url')} />
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
              Create a webhook
            </button>
          </form>
        </div>
      </div>
    </div>
  );
};
