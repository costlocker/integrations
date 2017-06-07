import React from 'react';
import { UIView } from 'ui-router-react';
import { Link, Button } from '../ui/Components';

const AnonymousUser = () => <em>Not logged in</em>;

const CostlockerUser = ({ user }) => {
  if (!user) {
    return <AnonymousUser />;
  }
  return (
    <div>
      <strong>
        {user.person.first_name} {user.person.last_name}
      </strong> ({user.company.name})
      &nbsp;<Button route='login' title='Switch Costlocker account' className="btn btn-default btn-sm" />
    </div>
  );
};

const BasecampUser = ({ user }) => {
  let userInfo = <AnonymousUser />;
  if (user) {
    userInfo = <span>
      {user.identity.first_name} {user.identity.last_name} ({user.identity.email_address})
    </span>;
  }
  return (
    <div>
      {userInfo}
      &nbsp;<Button route='basecamp' title='Basecamp accounts' className="btn btn-default btn-sm" />
    </div>
  );
};

const User = ({ auth }) => {
  return (
    <div>
      <div className="text-primary">
        <CostlockerUser user={auth.get('costlocker')} />
      </div>
      <div className="text-success">
        <BasecampUser user={auth.get('basecamp')} />
      </div>
    </div>
  );
};

const Navigation = () => {
  return (
    <ul className="nav nav-pills">
      <li><Link route='projects' title='Costlocker projects' /></li>
      <li><Link route='basecamp' title='Basecamp accounts' /></li>
      <li><Link route='sync' title='Synchronize' /></li>
      <li><Link route='events' title='Events' /></li>
      <li><Link route='settings' title='Settings' /></li>
    </ul>
  );
};

export default function Layout({ auth }) {
  return (
    <div>
      <nav className="navbar navbar-default">
        <div className="container">
          <div className="navbar-header">
            <div className="navbar-brand">
              <a href="/">
                <img alt="" src="https://cdn-images-1.medium.com/max/1200/1*BLdn5GGWwijxJkcr0I0rgg.png" width="40px" />
              </a>
            </div>
            <div className="navbar-brand">
              <span className="text-primary">Costlocker</span><br />
              <span className="text-success">Basecamp</span>
            </div>
          </div>
          <div className="navbar-text navbar-right text-right">
            <User auth={auth} />
          </div>
        </div>
      </nav>
      <div className="container">
        <div className="row">
          <div className="col-sm-12">
            {auth.get('costlocker') ? <Navigation /> : ''}
          </div>
        </div>
        <div className="row">
          <div className="col-sm-12">
            <UIView />
          </div>
        </div>
      </div>
    </div>
  );
};
