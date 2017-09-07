import React from 'react';
import { UIView } from '@uirouter/react';
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
  if (user) {
    return <User name={`${user.person.first_name} ${user.person.last_name}`} company={user.company.name} />;
  } else {
    return <User name="Login" company="Costlocker" />;
  }
};

const HarvestUser = ({ user }) => {
  if (user) {
    return <User name={user.user_name} company={user.company_name} />;
  } else {
    return <User name="Login" company="Harvest" />;
  }
};

const Users = ({ auth }) => {
  return <div>
    <span className="hide">Accounts</span>
    <Logo app="costlocker" color='white' />
    <CostlockerUser user={auth.get('costlocker')} />
    <Logo app="harvest" />
    <HarvestUser user={auth.get('harvest')} />
  </div>;
};

const Navigation = ({ routes }) => {
  return (
    <ul className="nav navbar-nav">
      {routes.map(({ title, className }) => (
        <li key={title}>
          <a onClick={() => null} className={className}>{title}</a>
        </li>
      ))}
    </ul>
  );
};

export default function App({ auth, steps }) {
  const stepsItems = [];
  steps.titles.forEach((title, index) => {
    const i = index + 1;
    if (steps.isInvalidStep(i)) {
      stepsItems.push(<li key={i} className="text-muted">{title}</li>);
    } else if (i === steps.getCurrentStep()) {
      stepsItems.push(<li key={i} className="active"><strong>{title}</strong></li>);
    } else {
      stepsItems.push(<li key={i}><a href="" onClick={(e) => steps.goToStep(i, e)}>{title}</a></li>);
    }
    if (i < steps.titles.length) {
      stepsItems.push(<li key={`separator-${i}`} className="wizard-step text-muted"><span className="fa fa-angle-right" /></li>)
    }
  });
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
                <ul className="nav navbar-nav">
                  <li className="active">
                    <a onClick={() => null}>Import project</a>
                  </li>
                </ul>
              </div>
              <div className="navbar-right text-right">
                <Navigation routes={[
                  { title: <Users auth={auth} />, className: 'users' },
                ]} />
              </div>
            </div>
          </div>
        </div>
      </nav>
      <nav className="nav-breadcrumbs-wizard">
        <div className="container">
          <div className="row">
            <div className="col-sm-12">
              <ol className="breadcrumb">{stepsItems}</ol>
            </div>
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
