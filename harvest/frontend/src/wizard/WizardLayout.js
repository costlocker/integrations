import React from 'react';
import { UIView } from 'ui-router-react';

import User from '../harvest/User';

const anonymousUser = <em>Not logged in</em>;

export default function WizardLayout({ isNotLoggedIn, harvestUser }) {
  const user = isNotLoggedIn ? anonymousUser : <User harvestUser={harvestUser} />;
  return (
    <div>
      <div className="row bg-info">
        <div className="col-sm-6">
          Harvest -> Costlocker
        </div>
        <div className="col-sm-6 text-right">
          {user}
        </div>
      </div>
      <div className="row">
        <div className="col-sm-12">
          <UIView />
        </div>
      </div>
    </div>
  );
};
