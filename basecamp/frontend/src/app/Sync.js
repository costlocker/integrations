import React from 'react';
import { Set } from 'immutable';

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
    return <span>Loading...</span>;
  } else if (!costlockerProjects.length) {
    return <span>No projects available</span>;
  }

  const isBasecampProjectCreated = syncForm.get('mode') === 'create';

  const isExistingProjectEdited = syncForm.editedProject;
  const availableCostlockerProjects = costlockerProjects.filter(
    p => syncForm.editedProject ? p.id == syncForm.editedProject : !p.basecamps.length
  )
  const editedProject = syncForm.editedProject ? availableCostlockerProjects[0] : null;
  const connectedBasecamp = syncForm.editedProject ? editedProject.basecamps[0] : null;
  const availableBasecampProjects = isExistingProjectEdited
    ? basecampProjects.filter(p => p.id == availableCostlockerProjects[0].basecamps[0].id)
    : basecampProjects;


  const areMultipleCostlockerProjectsAllowed = isBasecampProjectCreated;
  let selectedCostlockerProjects = syncForm.get('costlockerProject').toJS();
  if (!areMultipleCostlockerProjectsAllowed) {
    selectedCostlockerProjects = selectedCostlockerProjects.length ? selectedCostlockerProjects[0] : '';
  }
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
            <strong>{editedProject.name}</strong> <span className="label label-default">{editedProject.client.name}</span></li>
          <li>
            Basecamp account:
            <strong>{connectedBasecamp.account.name}</strong> <span className="label label-default">{connectedBasecamp.account.product}</span>
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
          <select required
            className="form-control" name="costlockerProject" id="costlockerProject"
            multiple={areMultipleCostlockerProjectsAllowed} size={areMultipleCostlockerProjectsAllowed ? 10 : null}
            value={selectedCostlockerProjects} onChange={setCostlockerProject}
          >
            <option></option>
            {availableCostlockerProjects.map(project => (
              <option key={project.id} value={project.id}>
                {project.name} ({project.client.name})
              </option>
            ))}
          </select>
        </div>
        <BasecampAccountSelect title='Choose a Basecamp acccount to export it to' accounts={basecampAccounts} syncForm={syncForm} />
        <BasecampCompaniesSelect basecampCompanies={basecampCompanies} syncForm={syncForm} isBasecampProjectCreated={isBasecampProjectCreated} />
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
            <label>
              <input type="radio" name="mode" value="add"
                checked={!isBasecampProjectCreated} onChange={syncForm.set('mode')} />
              Add to an existing project in Basecamp
            </label>
          </div>
        </div>
        {!isBasecampProjectCreated &&
        <div className="form-group">
          <label htmlFor="basecampProject">Basecamp project</label>
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
        </div>
        }
      </div>
      )}
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
      <button type="submit" className="btn btn-primary">Synchronize</button>
    </form>
  </div>;
};
