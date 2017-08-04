import React from 'react';
import { UIView } from 'ui-router-react';
import { Link } from './Components';
import { Logo } from './Images';

const User = ({ name, company }) => {
  return (
    <div className="user">
      <strong>{name}</strong>
      <small>{company}</small>
    </div>
  );
};

const CostlockerUser = ({ user }) => {
  return <User name={`${user.person.first_name} ${user.person.last_name}`} company={user.company.name} />;
};

const BasecampUser = ({ user }) => {
  if (user) {
    return <User name={`${user.first_name} ${user.last_name}`} company={user.email_address} />;
  } else {
    return <User name="Login" company="Basecamp" />;
  }
};

const AnonymousUser = ({ isRouteActive }) => (
  <div>
    <Logo app="costlocker" color={isRouteActive('login') ? 'blue' : 'white'} />
    <User name="Login" company="Costlocker" />
  </div>
)

const Users = ({ auth, isRouteActive }) => {
  return <div>
    <Logo app="costlocker" color={isRouteActive('accounts') ? 'blue' : 'white'} />
    <CostlockerUser user={auth.get('costlocker')} />
    <Logo app="basecamp" />
    <BasecampUser user={auth.get('basecamp')} />
  </div>;
}

const Navigation = ({ isRouteActive, routes, className }) => {
  return (
    <ul className={`nav navbar-nav ${className}`}>
      {routes.map(({ route, params, title, activeRoute, className }) => (
      <li key={route} className={isRouteActive(route) || isRouteActive(activeRoute) ? `active ${className}` : className}>
        <Link route={route} params={params} title={title} />
      </li>
      ))}
    </ul>
  );
};

export default function Layout({ auth, isRouteActive }) {
  return (
    <div>
      <nav className="navbar navbar-default">
        <div className="container">
          <div>
            {auth.get('costlocker') ? <Navigation isRouteActive={isRouteActive} routes={[
              { route: 'projects', title: 'Projects' },
              { route: 'sync', title: 'Add project', params: { clProject: null } },
              { route: 'events', title: 'Events', params: { clProject: null } },
              { route: 'settings', title: 'Settings' },
            ]} /> : ''}
          </div>
          <div>
            <Navigation className="navbar-right text-right" isRouteActive={isRouteActive} routes={[
              { route: 'accounts', activeRoute: 'login', className: 'users', title: auth.get('costlocker')
                ? <Users auth={auth} isRouteActive={isRouteActive} />
                : <AnonymousUser isRouteActive={isRouteActive} />
              },
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
