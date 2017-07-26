import React from 'react';
import { UIView } from '@uirouter/react';
import { Link } from './Components';
import Loading from './Loading';
import { appState } from '../state';
import { isRouteActive } from '../Router';

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

const Navigation = ({ routes }) => {
  return (
    <ul className="nav navbar-nav">
      {routes.map(({ route, params, title, activeRoute }) => (
        <li key={route} className={isRouteActive(route) || isRouteActive(activeRoute) ? 'active' : null}>
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
        <div className="container">
          <div className="navbar-header">
            <div className="navbar-brand">
              <a href="/">
                <img title="Costlocker" alt="Costlocker" src="https://cdn-images-1.medium.com/max/1200/1*BLdn5GGWwijxJkcr0I0rgg.png" />
              </a>
            </div>
          </div>
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
              { route: 'login', title: auth.get('costlocker') ? <User auth={auth} /> : 'Login' },
            ]} />
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
