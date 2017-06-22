import React from 'react';
import { UIView } from 'ui-router-react';
import { ExternalLink } from '../ui/Components';

const AnonymousUser = () => <em>Not logged in</em>;

const HarvestUser = ({ user }) => {
  if (!user) {
    return <AnonymousUser />
  }
  return (
    <span>
      <strong>
        {user.user_name}
      </strong> ({user.company_name} <ExternalLink url={user.company_url} />)
    </span>
  );
};

const CostlockerUser = ({ user }) => {
  if (!user) {
    return <AnonymousUser />
  }
  return (
    <span>
      <strong>
        {user.person.first_name} {user.person.last_name}
      </strong> ({user.company.name})
    </span>
  );
};

const User = ({ auth }) => {
  return <div>
    <span title="Costlocker user">
      <CostlockerUser user={auth.costlocker} />
    </span>
    <span className="text-muted">&nbsp;/&nbsp;</span>
    <span title="Basecamp user">
      <HarvestUser user={auth.harvest} />
    </span>
  </div>;
};

export default function App({ auth, steps }) {
  const stepsItems = [];
  steps.titles.forEach((title, index) => {
    const i = index + 1;
    if (steps.isInvalidStep(i)) {
      stepsItems.push(<li key={i} className="text-muted">{title}</li>);
    } else if (i === steps.getCurrentStep()) {
      stepsItems.push(<li key={i} className="text-primary"><strong>{title}</strong></li>);
    } else {
      stepsItems.push(<li key={i}><a href="" onClick={(e) => steps.goToStep(i, e)}>{title}</a></li>);
    }
  });
  return (
    <div>
      <nav className="navbar navbar-default">
        <div className="container">
          <div className="navbar-header">
            <div className="navbar-brand">
              <a href="/">
                <img title="Costlocker" alt="Costlocker" src="https://cdn-images-1.medium.com/max/1200/1*BLdn5GGWwijxJkcr0I0rgg.png" />
                &nbsp;+&nbsp;
                <img title="Harvest" alt="Harvest" src="https://www.getharvest.com/assets/press/harvest-logo-icon-77a6f855102e2f85a7fbe070575f293346a643c371a49ceff341d2814e270468.png" />
              </a>
            </div>
          </div>
          <div className="navbar-text navbar-right text-right">
            <User auth={auth} />
          </div>
        </div>
      </nav>
      <nav className="nav-breadcrumbs">
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
