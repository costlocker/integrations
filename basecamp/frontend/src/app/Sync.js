import React from 'react';
import Loading from '../ui/Loading';
import { RadioButtons, Link, Errors } from '../ui/Components';
import { isNotLoggedInBasecamp } from '../state';

const BasecampAccountSelect = ({ title, accounts, syncForm, isAccountNotAvailable }) => (
  <div className="form-group">
    <label htmlFor="account">{title}</label><br />
    {accounts.length && !isNotLoggedInBasecamp() ? (
      <div>
        <RadioButtons
          items={accounts
            .filter(account => account.isMyAccount)
            .map(
              personAccount => ({
                id: personAccount.account.id,
                title: `${personAccount.account.name} (${personAccount.account.identity.email_address})`
              })
            )
          }
          isActive={type => syncForm.get('account') == type.id}
          onChange={syncForm.set('account')}
        />
        {isAccountNotAvailable ? (
          <p className="help-block text-danger">
            Selected basecamp account is not working. Did your trial expire? Try to <Link route="accounts" title="reconnect Basecamp account" />
          </p>
        ) : null}
      </div>
    ) : (
      <p className="help-block text-danger">
        You cannot select an account until you are <Link route="accounts" title="logged in Basecamp" />.
      </p>
    )}
  </div>
);

const BasecampCompaniesSelect = ({ basecampCompanies, isBasecampProjectCreated, syncForm }) => {
  if (!isBasecampProjectCreated || !basecampCompanies.length) {
    return null;
  }
  return <div className="form-group">
      <label htmlFor="basecampClassicCompanyId">Choose a Basecamp company (client)</label>
      <select
        className="form-control" name="basecampClassicCompanyId" id="basecampClassicCompanyId"
        value={syncForm.get('basecampClassicCompanyId')} onChange={syncForm.set('basecampClassicCompanyId')}
      >
        {basecampCompanies.map(company => (
          <option key={company.id} value={company.id}>
            {company.name}
          </option>
        ))}
      </select>
    </div>;
};

export default function Sync({ costlockerProjects, basecamp, basecampAccounts, syncForm, isExistingProjectEdited }) {
  if (!costlockerProjects) {
    return <Loading title="Loading projects" />;
  } else if (!costlockerProjects.length) {
    return <span>No projects available</span>;
  }

  const isBasecampProjectCreated = syncForm.get('mode') === 'create';

  const indexedSelectedProjects = {}, unmappedCostlockerProjects = [];
  costlockerProjects.forEach(p => {
    if (syncForm.get('costlockerProject').includes(p.id)) {
      indexedSelectedProjects[p.id] = p;
    } else if (!p.basecamps.length) {
      unmappedCostlockerProjects.push(p);
    }
  });
  const selectedCostlockerProjects = syncForm.get('costlockerProject').map(id => indexedSelectedProjects[id]);

  const editedProject = isExistingProjectEdited ? selectedCostlockerProjects.first() : null;
  if (isExistingProjectEdited && !editedProject) {
    return <Errors title="Unknown project" error="Selected project not found or is not running" />;
  }
  const connectedBasecamp = isExistingProjectEdited ? editedProject.basecamps[0] : null;

  const selectedBasecampAccounts = basecampAccounts
    .filter(a => a.account.id == syncForm.get('account'));
  const selectedBasecampAccount = selectedBasecampAccounts.length ? selectedBasecampAccounts[0] : null;
  const canBeSynchronizedFromBasecamp = selectedBasecampAccount
    ? selectedBasecampAccount.account.canBeSynchronizedFromBasecamp : false;
  const optionalSetIfAvailable = (type) => (e) => canBeSynchronizedFromBasecamp ? syncForm.set(type)(e) : null;

  const availableBasecampProjects = isExistingProjectEdited
    ? basecamp.get('projects').filter(p => p.id == editedProject.basecamps[0].id)
    : basecamp.get('projects');

  const errors =
    [
      { hasFailed: !selectedCostlockerProjects.size, error: 'Select at least one costlocker project' },
      { hasFailed: isNotLoggedInBasecamp(), error: <Link route="accounts" title="Login to Basecamp" /> },
      { hasFailed: !isNotLoggedInBasecamp() && !syncForm.get('account'), error: 'Select one Basecamp account' },
      { hasFailed: !isNotLoggedInBasecamp() && !basecamp.get('isAccountAvailable'), error: 'Select valid Basecamp account, or reconnect selected account' },
      { hasFailed: !isBasecampProjectCreated && selectedCostlockerProjects.size !== 1, error: 'Select one Basecamp project, if you still want to add project to an existing project' },
    ]
    .filter(validation => validation.hasFailed)
    .map(validation => validation.error);

  const isFormValid = errors.length === 0;

  return <div>
    <h1>{isExistingProjectEdited ? 'Refresh project' : 'Connect Costlocker project to Basecamp'}</h1>
    <form className="form" onSubmit={isFormValid ? syncForm.submit : () => null}>
      {editedProject ? (
      <div>
        <ul>
          <li>
            Costlocker project:
            &nbsp;<strong>{editedProject.name}</strong> <span className="label label-default">{editedProject.client.name}</span></li>
          <li>
            Basecamp account:
            &nbsp;<strong>{connectedBasecamp.account.name}</strong> <span className="label label-default">{connectedBasecamp.account.product}</span>
          </li>
          <li>
            Basecamp person:
            &nbsp;<strong>{connectedBasecamp.account.identity.first_name} {connectedBasecamp.account.identity.last_name}</strong>
            &nbsp;<span className="label label-default">{connectedBasecamp.account.identity.email_address}</span>
          </li>
        </ul>
        <BasecampAccountSelect title='Change a connected Basecamp acccount' accounts={basecampAccounts} syncForm={syncForm} isAccountNotAvailable={!basecamp.get('isAccountAvailable')} />
      </div>
      ) : (
      <div>
        <div className="form-group">
          <label htmlFor="costlockerProject">Costlocker project(s)</label>
          <div className="row">
            <div className="col-sm-12">
              <div className="input-group">
                <span className="input-group-addon">Add project</span>
                <select
                  className="form-control" name="costlockerProject" id="costlockerProject"
                  onChange={syncForm.checkItem('costlockerProject')}
                >
                  <option></option>
                  {unmappedCostlockerProjects.map(project => (
                    <option key={project.id} value={project.id}>
                      {project.name} ({project.client.name})
                    </option>
                  ))}
                </select>
              </div>
            </div>
          </div>
          {selectedCostlockerProjects.size ? (
          <div className="row">
            <div className="col-sm-12">
              <br />
              <div className="btn-toolbar">
                {selectedCostlockerProjects.map(project => (
                  <div key={project.id} className="btn-group">
                    <span className="btn btn-primary active" onClick={() => syncForm.checkItem('costlockerProject')({ target: { value: project.id } })}>
                      <span className="label label-danger">
                        <span className="fa fa-times" />
                      </span>
                      &nbsp;
                      {project.name}
                      &nbsp;
                      <span className="label label-warning">{project.client.name}</span>
                    </span>
                  </div>
                ))}
              </div>
            </div>
          </div>
          ) : null}
        </div>
        <BasecampAccountSelect title='Choose a Basecamp acccount to export it to' accounts={basecampAccounts} syncForm={syncForm} isAccountNotAvailable={!basecamp.get('isAccountAvailable')} />
        <BasecampCompaniesSelect basecampCompanies={basecamp.get('companies')} syncForm={syncForm} isBasecampProjectCreated={isBasecampProjectCreated} />
        <div className="row">
          <div className="col-sm-6">
            <div className="form-group">
              <label>How would you like to add this project to the Basecamp</label>
              <RadioButtons
                items={[
                  { id: 'create', title: 'Create a new project in Basecamp' },
                  { id: 'add', title: 'Add to an existing project in Basecamp' }
                ]}
                isActive={type => syncForm.get('mode') == type.id}
                onChange={syncForm.set('mode')}
              />
            </div>
          </div>
          <div className="col-sm-6">
            {!isBasecampProjectCreated &&
            <div className="form-group">
              <label htmlFor="basecampProject">Basecamp project</label>
              {selectedCostlockerProjects.size > 1 ? (
              <p className="text-danger">
                Select only one Costlocker project, if you want to update existing Basecamp project.<br />
                It's not allowed to link multiple Costlocker projects to one Basecamp project.
              </p>
              ) : (
              <select
                className="form-control" name="basecampProject" id="basecampProject"
                value={syncForm.get('basecampProject')} onChange={syncForm.set('basecampProject')}
              >
                <option></option>
                {availableBasecampProjects.map(project => (
                  <option key={project.id} value={project.id}>
                    {project.name}
                  </option>
                ))}
              </select>
              )}
            </div>
            }
          </div>
        </div>
      </div>
      )}
      <div className="row">
        <div className="col-sm-6">
          <div className="form-group">
            <h4>Costlocker &rarr; Basecamp</h4>
            <label>What is exported to Basecamp?</label>
            <div>
              <label className="checkbox-inline disabled">
                <input type="checkbox" disabled defaultChecked={true}
                  /> Project name
              </label>
              <label className="checkbox-inline">
                <input type="checkbox" name="areTodosEnabled"
                  onChange={syncForm.set('areTodosEnabled')} checked={syncForm.get('areTodosEnabled')}
                  /> Personnel costs are transformed to todolists
              </label>
            </div>
          </div>
          {syncForm.get('areTodosEnabled') &&
          <div className="form-group">
            <label>What should happen when something is deleted in the Costlocker?</label>
            <div>
              <label className="checkbox-inline">
                <input type="checkbox" name="deleteTasks"
                  onChange={syncForm.set('isDeletingTodosEnabled')} checked={syncForm.get('isDeletingTodosEnabled')}
                  /> Delete todos
              </label>
              <label className="checkbox-inline">
                <input type="checkbox" name="revokeAccess"
                  onChange={syncForm.set('isRevokeAccessEnabled')} checked={syncForm.get('isRevokeAccessEnabled')}
                  /> Revoke access to persons without todo
              </label>
            </div>
          </div>
          }
        </div>
        <div className="col-sm-6" data-unavailable={canBeSynchronizedFromBasecamp ? null : true}>
          <div className="form-group">
            <h4 title="Available only for Basecamp 3">
              Basecamp <span className="label label-danger">3</span> &rarr; Costlocker
            </h4>
            <label>What is exported to Costlocker?</label>
            <div>
              <label className="checkbox-inline">
                <input type="checkbox" name="areTasksEnabled"
                  onChange={optionalSetIfAvailable('areTasksEnabled')} checked={syncForm.get('areTasksEnabled')}
                  /> Todo items are transformed to tasks under activity.
              </label><br />
              {syncForm.get('areTasksEnabled') &&
              <label className="checkbox-inline">
                <input type="checkbox" name="isCreatingActivitiesEnabled"
                  onChange={optionalSetIfAvailable('isCreatingActivitiesEnabled')} checked={syncForm.get('isCreatingActivitiesEnabled')}
                  /> New todolists are transformed to an activity (if the activity already exists in Costlocker, no new activity is created).
              </label>
              }
            </div>
          </div>
          {syncForm.get('areTasksEnabled') &&
          <div>
            <div className="form-group">
              <label>What should happen when something is deleted in the Basecamp?</label>
              <div>
                <label className="checkbox-inline">
                  <input type="checkbox" name="isDeletingTasksEnabled"
                    onChange={optionalSetIfAvailable('isDeletingTasksEnabled')} checked={syncForm.get('isDeletingTasksEnabled')}
                    /> Delete tasks in Costlocker
                </label>
                <label className="checkbox-inline">
                  <input type="checkbox" name="isDeletingActivitiesEnabled"
                    onChange={syncForm.set('isDeletingActivitiesEnabled')} checked={syncForm.get('isDeletingActivitiesEnabled')}
                    /> Delete activities in Costlocker
                </label>
              </div>
            </div>
            <div className="form-group">
              <label>Webhooks</label>
              <div>
                <label className="checkbox-inline">
                  <input type="checkbox" name="isBasecampWebhookEnabled"
                    onChange={optionalSetIfAvailable('isBasecampWebhookEnabled')} checked={syncForm.get('isBasecampWebhookEnabled')}
                    /> Allow real-time webhook synchronization
                </label>
              </div>
            </div>
          </div>
          }
        </div>
      </div>
      {isFormValid ? (
        <button type="submit" className="btn btn-primary btn-block">Synchronize</button>
      ) : (
        <div>
          <Errors
            title="Please fix following issues before synchronizing projects"
            error={<ul>{errors.map(error => <li key={error}>{error}</li>)}</ul>}
            errorClassName="warning"
          />
          <span className="btn btn-primary btn-block disabled">Synchronize</span>
        </div>
      )}
    </form>
  </div>;
};
