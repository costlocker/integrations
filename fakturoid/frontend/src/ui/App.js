import React from 'react';
import { UIView } from 'ui-router-react';
import { Link } from './Components';
import { Logo } from './Images';
import { appState } from '../state';

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

const FakturoidUser = ({ user, isLoggedIn }) => {
  if (isLoggedIn) {
    return <User name={user.person.full_name} company={user.account.name} />;
  } else {
    return <em>Not logged in Fakturoid</em>;
  }
};

const Users = ({ auth, isRouteActive }) => {
  if (!auth.get('costlocker')) {
    return null;
  }
  return <div>
    <Logo app="costlocker" color={isRouteActive('login') ? 'blue' : 'white'} />
    <CostlockerUser user={auth.get('costlocker')} />
    <Logo app="fakturoid" />
    <FakturoidUser user={auth.get('fakturoid')} isLoggedIn={auth.get('isLoggedInFakturoid')} />
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

const hasSubnavigation = (isRouteActive) => isRouteActive('invoice');

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
          <div>
            {auth.get('costlocker') ? <Navigation isRouteActive={isRouteActive} routes={[
              { route: 'invoice', title: 'Invoices', params: { id: null } },
            ]} /> : ''}
          </div>
          <div className="navbar-right text-right">
            <Navigation isRouteActive={isRouteActive} routes={[
              { route: 'login', title: auth.get('costlocker') ? <Users auth={auth} isRouteActive={isRouteActive} /> : 'Login' },
            ]} />
          </div>
        </div>
      </nav>
      {hasSubnavigation(isRouteActive) ? (
        <UIView />
      ) : (
          <Page view={<UIView />} />
        )}
    </div>
  );
};

export const PageWithSubnav = ({ tabs }) => {
  const changeTab = (tab) => () => appState.cursor(['app']).set('activeTab', tab.id);
  const hasTab = (id) => tabs.filter(tab => tab.id === id).length;
  const getCss = (id, defaultClass) => {
    const activeTabInState = appState.cursor(['app', 'activeTab']).deref();
    const activeTab = activeTabInState && hasTab(activeTabInState) ? activeTabInState : tabs[0].id;
    return id === activeTab ? `${defaultClass} active` : defaultClass;
  };

  return <div>
    <nav className="nav-breadcrumbs">
      <div className="container">
        <div className="row">
          <div className="col-sm-12">
            <ol className="breadcrumb">
              {tabs.map(tab => (
                <li key={tab.id} className={getCss(tab.id, null)}>
                  <Link title={tab.name} action={changeTab(tab)} />
                </li>
              ))}
            </ol>
          </div>
        </div>
      </div>
    </nav>
    <Page
      view={<div className="tab-content">
        {tabs.map(tab => (
          <div key={tab.id} className={getCss(tab.id, "tab-pane")} id={tab.id}>{tab.content()}</div>
        ))}
      </div>}
    />
  </div>;
};
