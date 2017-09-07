import React from 'react';
import { UIView } from '@uirouter/react';
import { Link } from './Components';
import { Logo } from './Images';
import { appState } from '../state';
import { trans } from '../i18n';

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
    return <User name="Login" company="Fakturoid" />;
  }
};

const AnonymousUser = ({ isRouteActive }) => (
  <div>
    <span className="hide">Login</span>
    <Logo app="costlocker" color={isRouteActive('login') ? 'blue' : 'white'} />
    <div className="user">
      <strong>Login</strong>
      <small>Costlocker</small>
    </div>
  </div>
)

const Users = ({ auth, isRouteActive }) => {
  return <div>
    <span className="hide">{trans('login.accounts')}</span>
    <Logo app="costlocker" color={isRouteActive('login') ? 'blue' : 'white'} />
    <CostlockerUser user={auth.get('costlocker')} />
    <Logo app="fakturoid" />
    <FakturoidUser user={auth.get('fakturoid')} isLoggedIn={auth.get('isLoggedInFakturoid')} />
  </div>;
}

const Navigation = ({ isRouteActive, routes }) => {
  return (
    <ul className="nav navbar-nav">
      {routes.map(({ route, params, title, className }) => (
        <li key={route} className={isRouteActive(route) ? `active ${className}` : className}><Link route={route} params={params} title={title} /></li>
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
        <div className="container-fluid">
          <div className="navbar-header">
            <button
              type="button" className="navbar-toggle collapsed" data-toggle="collapse"
              data-target="#navbar-addon" aria-expanded="false"
            >
              <span className="sr-only">Toggle navigation</span>
              <span className="icon-bar"></span>
              <span className="icon-bar"></span>
              <span className="icon-bar"></span>
            </button>
          </div>
          <div className="navbar-collapse collapse" id="navbar-addon">
            <div className="container">
              <div>
                {auth.get('costlocker') ? <Navigation isRouteActive={isRouteActive} routes={[
                  { route: 'invoice', title: trans('page.invoices'), params: { id: null } },
                ]} /> : ''}
              </div>
              <div className="navbar-right text-right">
                <Navigation isRouteActive={isRouteActive} routes={[
                  {
                    route: 'login', className: 'users', title: auth.get('costlocker')
                      ? <Users auth={auth} isRouteActive={isRouteActive} />
                      : <AnonymousUser isRouteActive={isRouteActive} />
                  },
                ]} />
              </div>
            </div>
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
