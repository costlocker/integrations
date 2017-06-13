import React from 'react';

export default function Settings({ form, accounts }) {
  return <div>
    <div className="row">
      <div className="col-sm-12">
        <h1>Settings</h1>
      </div>
    </div>
    <div className="row">
      <div className="col-sm-12">
        <form className="form" onSubmit={form.submit}>
          <h4>Default configuration</h4>
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
          <h4>New project in Costlocker</h4>
          <div className="form-group">
            <label>What should happen when a new project is created in the Costlocker?</label>
            <div>
              <label className="checkbox-inline">
                <input type="checkbox" name="isCreatingBasecampProjectEnabled"
                  onChange={form.set('isCreatingBasecampProjectEnabled')} checked={form.get('isCreatingBasecampProjectEnabled')}
                  /> Create project in Basecamp
              </label>
            </div>
          </div>
          {form.get('isCreatingBasecampProjectEnabled') ? (
          <div>
            <div className="form-group">
              <label htmlFor="account">Choose a Basecamp used for creating a new project</label>
              <select
                className="form-control" name="account" id="account"
                value={form.get('account') ? form.get('account') : ''} onChange={form.set('account')}
              >
                <option></option>
                {accounts.basecamp.map(personAccount => {
                  return <option key={personAccount.account.id} value={personAccount.account.id}>
                    {personAccount.account.name} ({personAccount.account.identity.email_address})
                  </option>;
                })}
              </select>
            </div>
            <div className="form-group">
              <label htmlFor="account">Choose a Costlocker user used for accessing Costlocker</label>
              <select
                className="form-control" name="costlockerUser" id="costlockerUser"
                value={form.get('costlockerUser') ? form.get('costlockerUser') : ''} onChange={form.set('costlockerUser')}
              >
                <option></option>
                {accounts.costlocker.map(person => {
                  return <option key={person.email} value={person.email}>
                    {person.first_name} {person.last_name} ({person.email})
                  </option>;
                })}
              </select>
            </div>
          </div>
          ) : null}
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
