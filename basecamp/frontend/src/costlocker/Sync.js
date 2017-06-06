import React from 'react';

export default function Sync({ costlockerProjects, basecampProjects, basecampAccounts, syncForm }) {
  if (!costlockerProjects) {
    return <span>Loading...</span>;
  } else if (!costlockerProjects.length) {
    return <span>No projects available</span>;
  }
  const isBasecampProjectCreated = syncForm.get('mode') === 'create';

  return <div>
    <h1>Synchronize Costlocker & Basecamp</h1>
    <form className="form" onSubmit={syncForm.submit}>
      <div className="form-group">
        <label htmlFor="account">Choose a Basecamp acccount to export it to</label>
        <select
           className="form-control" name="account" id="account"
           value={syncForm.get('account')} onChange={syncForm.set('account')}
        >
          {basecampAccounts.map(account => (
            <option key={account.id} value={account.id}>
              {account.name}
            </option>
          ))}
        </select>
      </div>
      <div className="form-group">
        <label htmlFor="costlockerProject">Costlocker project</label>
        <select
          className="form-control" name="costlockerProject" id="costlockerProject"
          value={syncForm.get('costlockerProject')} onChange={syncForm.set('costlockerProject')}
        >
          {costlockerProjects.map(project => (
            <option key={project.id} value={project.id}>
              {project.name} ({project.client.name})
            </option>
          ))}
        </select>
      </div>
      <div className="form-group">
        <label>How would you like to add this project to the Basecamp</label>
        <div className="radio">
          <label>
            <input type="radio" name="mode" value="create"
              checked={isBasecampProjectCreated} onChange={syncForm.set('mode')} />
            Create a new project in Basecamp
          </label>
        </div>
        {basecampProjects.length &&
        <div className="radio">
          <label>
            <input type="radio" name="mode" value="add"
              checked={!isBasecampProjectCreated} onChange={syncForm.set('mode')} />
            Add to an existing project in Basecamp
          </label>
        </div>
        }
      </div>
      {basecampProjects.length && !isBasecampProjectCreated &&
      <div className="form-group">
        <label htmlFor="basecampProject">Basecamp project</label>
        <select
          className="form-control" name="basecampProject" id="basecampProject"
          value={syncForm.get('basecampProject')} onChange={syncForm.set('basecampProject')}
        >
          {basecampProjects.map(project => (
            <option key={project.id} value={project.id}>
              {project.name}
            </option>
          ))}
        </select>
      </div>
      }
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
