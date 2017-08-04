import React from 'react';
import Loading from '../ui/Loading';

const BasecampAccountSelect = ({ title, accounts, syncForm }) => (
  <div className="form-group">
    <label htmlFor="account">{title}</label>
    <select
      className="form-control" name="account" id="account"
      value={syncForm.get('account')} onChange={syncForm.set('account')}
    >
      {accounts.map(personAccount => {
        if (!personAccount.isMyAccount && personAccount.account.id != syncForm.get('account')) {
          return null;
        }
        return <option key={personAccount.account.id} value={personAccount.account.id}>
          {personAccount.account.name} ({personAccount.account.identity.email_address})
        </option>;
      })}
    </select>
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

export default function Sync({ costlockerProjects, basecampProjects, basecampCompanies, basecampAccounts, syncForm }) {
  if (!costlockerProjects) {
    return <Loading title="Loading projects" />;
  } else if (!costlockerProjects.length) {
    return <span>No projects available</span>;
  }

  const isBasecampProjectCreated = syncForm.get('mode') === 'create';

  const indexedSelectedProjects = {}, availableCostlockerProjects = [];
  costlockerProjects.forEach(p => {
    if (syncForm.get('costlockerProject').includes(p.id)) {
      indexedSelectedProjects[p.id] = p;
    } else if (!p.basecamps.length) {
      availableCostlockerProjects.push(p);
    }
  });
  const selectedCostlockerProjects = syncForm.get('costlockerProject').map(id => indexedSelectedProjects[id]);

  const isExistingProjectEdited = syncForm.editedProject;
  const editedProject = isExistingProjectEdited ? selectedCostlockerProjects.first() : null;
  const connectedBasecamp = isExistingProjectEdited ? editedProject.basecamps[0] : null;

  const selectedBasecampAccounts = basecampAccounts
    .filter(a => a.account.id == syncForm.get('account'));
  const selectedBasecampAccount = selectedBasecampAccounts.length ? selectedBasecampAccounts[0] : null;
  const canBeSynchronizedFromBasecamp = selectedBasecampAccount
    ? selectedBasecampAccount.account.canBeSynchronizedFromBasecamp : false;
  const optionalSetIfAvailable = (type) => (e) => canBeSynchronizedFromBasecamp ? syncForm.set(type)(e) : null;

  const availableBasecampProjects = isExistingProjectEdited
    ? basecampProjects.filter(p => p.id == editedProject.basecamps[0].id)
    : basecampProjects;

  let setCostlockerProject = (e) => {
    const projectId = e.target.value;
    syncForm.updateCostlockerProjects(
      set => (set.includes(projectId) ? set.delete(projectId) : set.add(projectId))
    );
  };

  return <div>
    <h1>{isExistingProjectEdited ? 'Edit project' : 'Connect Costlocker project to Basecamp'}</h1>
    <form className="form" onSubmit={syncForm.submit}>
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
        <BasecampAccountSelect title='Change a connected Basecamp acccount' accounts={basecampAccounts} syncForm={syncForm} />
      </div>
      ) : (
      <div>
        <div className="form-group">
          <label htmlFor="costlockerProject">Costlocker project(s)</label>
          {selectedCostlockerProjects.size ? (
          <div className="row">
            <div className="col-sm-12">
              <div className="btn-toolbar">
                {selectedCostlockerProjects.map(project => (
                  <div key={project.id} className="btn-group">
                    <span className="btn btn-default" onClick={() => setCostlockerProject({ target: { value: project.id } })}>
                      {project.name} <span className="label label-default">{project.client.name}</span>
                      &nbsp;<span className="fa fa-times text-danger" />
                    </span>
                  </div>
                ))}
              </div>
              <br />
            </div>
          </div>
          ) : null}
          <div className="row">
            <div className="col-sm-12">
              <div className="input-group">
                <span className="input-group-addon">Add project</span>
                <select
                  className="form-control" name="costlockerProject" id="costlockerProject"
                  onChange={setCostlockerProject}
                >
                  <option></option>
                  {availableCostlockerProjects.map(project => (
                    <option key={project.id} value={project.id}>
                      {project.name} ({project.client.name})
                    </option>
                  ))}
                </select>
              </div>
            </div>
          </div>
        </div>
        <BasecampAccountSelect title='Choose a Basecamp acccount to export it to' accounts={basecampAccounts} syncForm={syncForm} />
        <BasecampCompaniesSelect basecampCompanies={basecampCompanies} syncForm={syncForm} isBasecampProjectCreated={isBasecampProjectCreated} />
        <div className="row">
          <div className="col-sm-6">
            <div className="form-group">
              <label>How would you like to add this project to the Basecamp</label>
              <div className="radio">
                <label>
                  <input type="radio" name="mode" value="create"
                    checked={isBasecampProjectCreated} onChange={syncForm.set('mode')} />
                  Create a new project in Basecamp
                </label>
              </div>
              <div className="radio">
                <label className={selectedCostlockerProjects.size > 1 ? 'text-danger' : null}>
                  <input type="radio" name="mode" value="add"
                    checked={!isBasecampProjectCreated} onChange={syncForm.set('mode')} />
                  Add to an existing project in Basecamp
                </label>
              </div>
            </div>
            {!isBasecampProjectCreated &&
            <div className="form-group">
              <label htmlFor="basecampProject">Basecamp project</label>
              {selectedCostlockerProjects.size > 1 ? (
              <p className="text-muted">
                Please select only one Costlocker project.<br />
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
          <h4>Costlocker &rarr; Basecamp</h4>
          <div className="form-group">
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
          <h4 title="Available only for Basecamp 3">
            Basecamp <span className="label label-danger">3</span> &rarr; Costlocker
          </h4>
          <div className="form-group">
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
      {selectedCostlockerProjects.size ? (
        <button type="submit" className="btn btn-primary btn-block">Synchronize</button>
      ) : (
        <span className="btn btn-primary btn-block disabled">Synchronize</span>
      )}
    </form>
  </div>;
};
