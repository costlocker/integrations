import React from 'react';

export default function Projects({ settings, form }) {
  return <div>
    <div className="row">
      <div className="col-sm-12">
        <h1>Settings</h1>
      </div>
    </div>
    <div className="row">
      <div className="col-sm-12">
        <form className="form" onSubmit={form.submit}>
          <h4>Configuration of new projects</h4>
          <div className="form-group">
            <label>What is exported to Basecamp?</label>
            <div>
              <label className="checkbox-inline">
                <input type="checkbox" name="areTodosEnabled"
                  onChange={form.set('areTodosEnabled')} checked={form.get('areTodosEnabled')}
                  /> Personnel costs are transformed to todolists
              </label>
            </div>
          </div>
          <div className="form-group">
            <label>What should happen when something is deleted in the Costlocker?</label>
            <div>
              <label className="checkbox-inline">
                <input type="checkbox" name="deleteTasks"
                  onChange={form.set('isDeletingTodosEnabled')} checked={form.get('isDeletingTodosEnabled')}
                  /> Delete todos
              </label>
              <label className="checkbox-inline">
                <input type="checkbox" name="revokeAccess"
                  onChange={form.set('isRevokeAccessEnabled')} checked={form.get('isRevokeAccessEnabled')}
                  /> Revoke access to persons without todo
              </label>
            </div>
          </div>
          <h4>Webhooks</h4>
          <div className="form-group">
            <div>
              <label className="checkbox-inline">
                <input type="checkbox" disabled
                  onChange={form.set('isCostlockerWebhookEnabled')} checked={form.get('isCostlockerWebhookEnabled')}
                  /> Costlocker webhook
              </label>
              <p className="help-block">
                Webhook is automatically registered and validated after you save settings.
              </p>
            </div>
          </div>
          <button type="submit" className="btn btn-primary">Save settings</button>
        </form>
      </div>
    </div>
  </div>;
};
