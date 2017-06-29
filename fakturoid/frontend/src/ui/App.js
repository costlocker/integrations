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

const FakturoidUser = ({ user }) => {
  if (user) {
    return <span>
      {user.person.full_name} ({user.account.name})
    </span>;
  } else {
    return <em>Not logged in Fakturoid</em>;
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
    <span title="Fakturoid user">
      <FakturoidUser user={auth.get('fakturoid')} />
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
                <img title="Fakturoid" alt="Fakturoid" src="http://lh4.ggpht.com/f4MbYdkzKxy4H8RfxsbPXG8Ub0-Re6D2lvFgOEqKZTc8bNkCwJ5w_szn-CgVITdAOIU" />
              </a>
            </div>
          </div>
          <div>
            {auth.get('costlocker') ? <Navigation isRouteActive={isRouteActive} routes={[
              { route: 'invoice', title: 'Create invoice', params: { id: null } },
            ]} /> : ''}
          </div>
          <div className="navbar-right text-right">
            <Navigation isRouteActive={isRouteActive} routes={[
              { route: 'login', title: <User auth={auth} /> },
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
