import React from 'react';

import { ExternalLink, Link, Button, RadioButtons } from '../ui/Components';
import { Logo } from '../ui/Images';
import Loading from '../ui/Loading';

export default function Projects({ allProjects, disconnect, form }) {
  if (!allProjects) {
    return <Loading title="Loading projects" />;
  }
  const projects = allProjects.filter(p => p.basecamps.length);
  const notConnectedProjectsCount = allProjects.length - projects.length;

  return <div className={form.get('isSearching') ? 'reloading' : null}>
    <div className="row">
      <div className="col-sm-12">
        <h1>Projects</h1>
      </div>
    </div>
    <form className="form row">
      <div className="col-sm-12">
        <div className="form-group">
          <RadioButtons
            items={[
              { id: '', title: 'All' },
              { id: 'running', title: 'Running' },
              { id: 'finished', title: 'Finished' },
            ]}
            isActive={type => form.get('state') === type.id}
            onChange={form.get('isSearching') ? () => null : form.set('state')}
          />
        </div>
      </div>
    </form>
    <div className="row">
      <div className="col-sm-12">
        {projects.length ? (
        <table className="table table-striped table-hover table-valign">
          <thead>
            <tr>
              <th>Costlocker</th>
              <th>Basecamp</th>
              <th>Synchronization</th>
              <th width='300'>Links</th>
              <th width='300' className="text-center">Actions</th>
            </tr>
          </thead>
          <tbody>
            {projects.map(project => (
              <tr key={project.id}>
                <td>
                  {project.name} <span className="label label-default">{project.client.name}</span>
                </td>
                <td>
                  <em>{project.basecamp.account.name} <span className="label label-default">{project.basecamp.account.product}</span></em>
                </td>
                <td>
                  {project.basecamp.settings.areTodosEnabled ? <span className="text-primary">Costlocker &rarr; Basecamp</span> : ''}<br />
                  {project.basecamp.settings.areTasksEnabled ? <span className="text-success">Basecamp &rarr; Costlocker</span> : ''}
                </td>
                <td>
                  <ExternalLink url={project.url} className="text-primary first"
                    title={<span><Logo app="costlocker" color="blue" /> Open project</span>} />
                  <ExternalLink url={project.basecamp.url} className="text-success"
                    title={<span><Logo app="basecamp" /> Open project</span>} />
                </td>
                <td className="text-right">
                  <Link route='sync' params={{ clProject: project.id }}
                    title={<span><i className="fa fa-refresh"></i> Refresh</span>} className="btn btn-sm btn-primary" />
                  &nbsp;&nbsp;
                  <Link route='events' params={{ clProject: project.id }}
                    title={<span><i className="fa fa-th-list"></i> Events</span>} className="btn btn-sm btn-info" />
                  &nbsp;&nbsp;
                  <Button action={() => disconnect(project.id)}
                    title={<span><i className="fa fa-trash"></i> Disconnect</span>} className="btn btn-sm btn-danger" />
                </td>
              </tr>
            ))}
          </tbody>
        </table>
        ) : (
        <p className="text-muted">No projects are synchronized</p>
        )}
      </div>
    </div>
    {notConnectedProjectsCount &&
    <div className="row">
      <div className="col-sm-12">
        <Link route='sync' title={<span>Connect new project ({notConnectedProjectsCount})</span>} className="btn btn-success btn-block" />
      </div>
    </div>
    }
  </div>;
};
