import React from 'react';
import { ExternalLink } from '../ui/Components';

export default function Settings({ form, accounts }) {
  return <form className="form" onSubmit={form.submit}>
    <div className="row">
      <div className="col-sm-12">
        <h1>Settings</h1>
      </div>
    </div>
    <div className="row">
      <div className="col-sm-12">
        <div className="row">
          <div className="col-sm-6">
            <h4>Costlocker &rarr; Basecamp</h4>
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
                    /> Delete todos in Basecamp
                </label>
                <label className="checkbox-inline">
                  <input type="checkbox" name="revokeAccess"
                    onChange={form.set('isRevokeAccessEnabled')} checked={form.get('isRevokeAccessEnabled')}
                    /> Revoke Basecamp access to persons without todo
                </label>
              </div>
            </div>
            <div className="form-group">
              <label>Webhooks</label>
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
          </div>
          <div className="col-sm-6">
            <h4 title="Available only for Basecamp 3">
              Basecamp <span className="label label-danger">3</span> &rarr; Costlocker
            </h4>
            <div className="form-group">
              <label>What is exported to Costlocker?</label>
              <div>
                <label className="checkbox-inline">
                  <input type="checkbox" name="areTasksEnabled"
                    onChange={form.set('areTasksEnabled')} checked={form.get('areTasksEnabled')}
                    /> Todo items are transformed to tasks under activity.
                </label>
              </div>
            </div>
            <div className="form-group">
              <label>What should happen when something is deleted in the Basecamp?</label>
              <div>
                <label className="checkbox-inline">
                  <input type="checkbox" name="isDeletingTasksEnabled"
                    onChange={form.set('isDeletingTasksEnabled')} checked={form.get('isDeletingTasksEnabled')}
                    /> Delete tasks in Costlocker
                </label>
              </div>
            </div>
            <div className="form-group">
              <label>Webhooks <ExternalLink url="https://m.signalvnoise.com/new-in-basecamp-3-webhooks-e3c9d26340c0" /></label>
              <div>
                <label className="checkbox-inline">
                  <input type="checkbox" name="isBasecampWebhookEnabled"
                    onChange={form.set('isBasecampWebhookEnabled')} checked={form.get('isBasecampWebhookEnabled')}
                    /> Allow real-time webhook synchronization
                </label>
                <p className="help-block">
                  Webhook is automatically registered and validated during project synchronization.
                </p>
              </div>
            </div>
            <div className="form-group">
              <label>What should happen when a new project is created in the Basecamp?</label>
              <div>
                <p className="text-muted">
                  Basecamp does not notify us when new project is created.<br />
                  At first you have to import Costlocker project to Basecamp.
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div className="row">
      <div className="col-sm-12">
          <button type="submit" className="btn btn-primary btn-block">Save settings</button>
      </div>
    </div>
  </form>;
};
