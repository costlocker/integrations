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
            <div className="form-group">
              <h4>Costlocker &rarr; Basecamp</h4>
              <label>When data is exported to Basecamp</label>
              <div>
                <label className="checkbox-inline">
                  <input type="checkbox" name="areTodosEnabled"
                    onChange={form.set('areTodosEnabled')} checked={form.get('areTodosEnabled')}
                    /> Transform personnel costs to todo lists
                </label>
              </div>
            </div>
            <div className="form-group">
              <label>When something is deleted in Costlocker</label>
              <div>
                <label className="checkbox-inline">
                  <input type="checkbox" name="deleteTasks"
                    onChange={form.set('isDeletingTodosEnabled')} checked={form.get('isDeletingTodosEnabled')}
                    /> Delete todos in Basecamp
                </label>
                <label className="checkbox-inline">
                  <input type="checkbox" name="revokeAccess"
                    onChange={form.set('isRevokeAccessEnabled')} checked={form.get('isRevokeAccessEnabled')}
                    /> Revoke Basecamp access for people with no todos
                </label>
              </div>
            </div>
            <div className="form-group">
              <label>Webhooks</label>
              <div>
                <label className="checkbox-inline">
                  <input type="checkbox" disabled defaultChecked /> Allow real-time synchronization with Costlocker
                </label>
                <p className="help-block">
                  Webhook is automatically registered and validated during project synchronization.
                </p>
              </div>
            </div>
            <div className="form-group">
              <label>When a new project is created in Costlocker</label>
              <div>
                <label className="checkbox-inline">
                  <input type="checkbox" name="isCreatingBasecampProjectEnabled"
                    onChange={form.set('isCreatingBasecampProjectEnabled')} checked={form.get('isCreatingBasecampProjectEnabled')}
                    /> Create a project in Basecamp
                </label>
              </div>
            </div>
            {form.get('isCreatingBasecampProjectEnabled') ? (
            <div className="form-group">
              <label htmlFor="account">Choose a Basecamp account to create new projects</label>
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
            ) : null}
            {form.get('isCreatingBasecampProjectEnabled') ? (
            <div className="form-group">
              <label htmlFor="account">Choose a Costlocker user to access Costlocker</label>
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
            ) : null}
          </div>
          <div className="col-sm-6">
            <div className="form-group">
              <h4 title="Available only for Basecamp 3">
                Basecamp <span className="label label-danger">3</span> &rarr; Costlocker
              </h4>
              <label>When data is exported to Costlocker</label>
              <div>
                <label className="checkbox-inline">
                  <input type="checkbox" name="areTasksEnabled"
                    onChange={form.set('areTasksEnabled')} checked={form.get('areTasksEnabled')}
                    /> Transform todo items to activity tasks
                </label><br />
                <label className="checkbox-inline">
                  <input type="checkbox" name="isCreatingActivitiesEnabled"
                    onChange={form.set('isCreatingActivitiesEnabled')} checked={form.get('isCreatingActivitiesEnabled')}
                    /> Transform new todo lists to activities (if they exist in Costlocker, new ones are not created)
                </label>
              </div>
            </div>
            <div className="form-group">
              <label>When something is deleted in Basecamp</label>
              <div>
                <label className="checkbox-inline">
                  <input type="checkbox" name="isDeletingTasksEnabled"
                    onChange={form.set('isDeletingTasksEnabled')} checked={form.get('isDeletingTasksEnabled')}
                    /> Delete tasks in Costlocker
                </label>
                <label className="checkbox-inline">
                  <input type="checkbox" name="isDeletingActivitiesEnabled"
                    onChange={form.set('isDeletingActivitiesEnabled')} checked={form.get('isDeletingActivitiesEnabled')}
                    /> Delete activities in Costlocker
                </label>
              </div>
            </div>
            <div className="form-group">
              <label>Webhooks <ExternalLink url="https://m.signalvnoise.com/new-in-basecamp-3-webhooks-e3c9d26340c0" /></label>
              <div>
                <label className="checkbox-inline">
                  <input type="checkbox" name="isBasecampWebhookEnabled"
                    onChange={form.set('isBasecampWebhookEnabled')} checked={form.get('isBasecampWebhookEnabled')}
                    /> Allow real-time synchronization with Basecamp
                </label>
                <p className="help-block">
                  Webhook is automatically registered and validated during project synchronization.
                </p>
              </div>
            </div>
            <div className="form-group">
              <label>When a new project is synchronized in Basecamp</label>
              <div>
                <p className="text-muted">
                  Currently, it is not possible to automatically import newly created projects into Costlocker.
                  It’s required to import Costlocker projects to Basecamp first.
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
