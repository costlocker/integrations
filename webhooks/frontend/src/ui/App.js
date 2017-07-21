import React from 'react';
import { UIView } from 'ui-router-react';
import { Link } from './Components';

const CostlockerUser = ({ user }) => {
  return (
    <span>
      <strong>
        {user.person.first_name} {user.person.last_name}
      </strong> ({user.company.name})
    </span>
  );
};

const User = ({ auth }) => {
  if (!auth.get('costlocker')) {
    return null;
  }
  return <div>
    <span title="Costlocker user">
      <CostlockerUser user={auth.get('costlocker')} />
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

export const Page = ({ view }) =>
  <div className='container'>
    <div className="row">
      <div className="col-sm-12">
        {view}
      </div>
    </div>
  </div>;

export function App({ auth, isRouteActive }) {
  return (
    <div>
      <nav className="navbar navbar-default">
        <div className="container">
          <div className="navbar-header">
            <div className="navbar-brand">
              <a href="/">
                <img title="Costlocker" alt="Costlocker" src="https://cdn-images-1.medium.com/max/1200/1*BLdn5GGWwijxJkcr0I0rgg.png" />
              </a>
            </div>
            <span className="navbar-text">Webhooks Manager</span>
          </div>
          <div>
            {auth.get('costlocker') ? <Navigation isRouteActive={isRouteActive} routes={[
              { route: 'webhooks', title: 'Webhooks' },
            ]} /> : ''}
          </div>
          <div className="navbar-right text-right">
            <Navigation isRouteActive={isRouteActive} routes={[
              { route: 'login', title: auth.get('costlocker') ? <User auth={auth} /> : 'Login' },
            ]} />
          </div>
        </div>
      </nav>
      <Page view={<UIView />} />
    </div>
  );
};
