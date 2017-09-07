import React from 'react';
import { UIView } from '@uirouter/react';
import { Link } from './Components';
import { Logo } from './Images';
import Loading from './Loading';
import { appState } from '../state';
import { isRouteActive } from '../Router';

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

const AnonymousUser = () => (
  <div>
    <span className="hide">Login</span>
    <Logo app="costlocker" color={isRouteActive('login') ? 'blue' : 'white'} />
    <User name="Login" company="Costlocker" />
  </div>
)

const Users = ({ auth }) => {
  return <div>
    <span className="hide">Accounts</span>
    <Logo app="costlocker" color={isRouteActive('login') ? 'blue' : 'white'} />
    <CostlockerUser user={auth.get('costlocker')} />
  </div>;
}

const Navigation = ({ routes }) => {
  return (
    <ul className="nav navbar-nav">
      {routes.map(({ route, params, title, activeRoute, className }) => (
        <li key={route} className={isRouteActive(route) || isRouteActive(activeRoute) ? `active ${className}` : className}>
          <Link route={route} params={params} title={title} />
        </li>
      ))}
    </ul>
  );
};

const hasSubnavigation = () => !isRouteActive('login');

export const Page = ({ view }) =>
  <div className='container'>
    <div className="row">
      <div className="col-sm-12">
        {view}
      </div>
    </div>
  </div>;

export function App({ auth, footer }) {
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
                {auth.get('costlocker') ?
                  <Navigation routes={[
                    { route: 'webhooks', title: 'Webhooks', activeRoute: 'webhook' },
                  ]} /> :
                  <span className="navbar-text">Webhooks</span>
                }
              </div>
              <div className="navbar-right text-right">
                <Navigation routes={[
                  { route: 'login', className: 'users', title: auth.get('costlocker') ? <Users auth={auth} /> : <AnonymousUser /> },
                ]} />
              </div>
            </div>
          </div>
        </div>
      </nav>
      {hasSubnavigation() ? (
        <UIView />
      ) : (
        <Page view={<UIView />} />
      )}
      <footer className="footer">
        <div className="container">
          {footer}
        </div>
      </footer>
    </div>
  );
};

export const PageWithSubpages = ({ pages, content }) => {
  const getCss = (route, defaultClass) => {
    return isRouteActive(route) ? `${defaultClass} active` : defaultClass;
  };
  const view = appState.cursor(['app', 'isSendingForm']).deref()
    ? <Loading title="Processing a change" />
    : content(<UIView />);

  return <div>
    <nav className="nav-breadcrumbs">
      <div className="container">
        <div className="row">
          <div className="col-sm-12">
            <ol className="breadcrumb">
              {pages.map(page => (
                <li key={page.route} className={getCss(page.route, null)}>
                  <Link title={page.name} {...page} />
                </li>
              ))}
            </ol>
          </div>
        </div>
      </div>
    </nav>
    <Page view={view} />
  </div>;
};
