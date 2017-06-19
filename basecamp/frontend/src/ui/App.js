import React from 'react';
import { UIView } from 'ui-router-react';
import { Link } from '../ui/Components';

const CostlockerUser = ({ user }) => {
  return (
    <span>
      <strong>
        {user.person.first_name} {user.person.last_name}
      </strong> ({user.company.name})
    </span>
  );
};

const BasecampUser = ({ user }) => {
  if (user) {
    return <span>
      {user.first_name} {user.last_name} ({user.email_address})
    </span>;
  } else {
    return <em>Not logged in Basecamp</em>;
  }
};

const User = ({ auth }) => {
  if (!auth.get('costlocker')) {
    return null;
  }
  return <div>
    <span title="Costlocker user">
      <CostlockerUser user={auth.get('costlocker')} />
    </span>
    <span className="text-muted">&nbsp;/&nbsp;</span>
    <span title="Basecamp user">
      <BasecampUser user={auth.get('basecamp')} />
    </span>
  </div>;
}

const Navigation = ({ isRouteActive, routes }) => {
  return (
    <ul className="nav navbar-nav">
      {routes.map(({ route, params, title }) => (
      <li key={route} className={isRouteActive(route) ? 'active' : null}><Link route={route} params={params} title={title} /></li>
      ))}
    </ul>
  );
};

export default function Layout({ auth, isRouteActive }) {
  return (
    <div>
      <nav className="navbar navbar-default">
        <div className="container">
          <div className="navbar-header">
            <div className="navbar-brand">
              <a href="/">
                <img title="Costlocker" alt="Costlocker" src="https://cdn-images-1.medium.com/max/1200/1*BLdn5GGWwijxJkcr0I0rgg.png" />
                &nbsp;+&nbsp;
                <img title="Basecamp" alt="Basecamp" src="https://freeter.io/embedding-web-apps/project-management/basecamp.png" />
              </a>
            </div>
          </div>
          <div>
            {auth.get('costlocker') ? <Navigation isRouteActive={isRouteActive} routes={[
              { route: 'projects', title: 'Projects' },
              { route: 'sync', title: 'Add project', params: { clProject: null } },
              { route: 'events', title: 'Events', params: { clProject: null } },
              { route: 'settings', title: 'Settings' },
            ]} /> : ''}
          </div>
          <div className="navbar-right text-right">
            <Navigation isRouteActive={isRouteActive} routes={[
              { route: 'accounts', title: <User auth={auth} /> },
              { route: 'help', title: <span className="fa fa-question-circle"></span> },
            ]} />
          </div>
        </div>
      </nav>
      <div className="container">
        <div className="row">
          <div className="col-sm-12">
            <UIView />
          </div>
        </div>
      </div>
    </div>
  );
};
