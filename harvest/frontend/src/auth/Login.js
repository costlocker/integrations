import React from 'react';

import HarvestLogin from './HarvestLogin';
import CostlockerLogin from './CostlockerLogin';
import { HarvestUser, CostlockerUser } from './User';

export default function Login({ isLoggedIn, auth, goToNextStep, handleHarvestLogin, loginUrl, clLoginError }) {
  let navigation = <hr />;
  if (isLoggedIn) {
      navigation =
        <div className="bg-success">
          <hr />
          <button className="btn btn-success btn-lg" onClick={goToNextStep}>
            Continue to Projects
          </button>
          <hr />
        </div>;
  }
  return (
    <div>
      <div className="row">
        <div className="col-sm-6">
          <h2>Harvest User</h2>
          <HarvestUser user={auth.harvest} />
        </div>
        <div className="col-sm-6">
          <h2>Costlocker User</h2>
          <CostlockerUser user={auth.costlocker} />
        </div>
      </div>
      <div className="row">
        <div className="col-sm-12 text-center">
          {navigation}
        </div>
      </div>
      <div className="row">
        <div className="col-sm-6">
          <h2>Change Harvest Account</h2>
          <HarvestLogin handleHarvestLogin={handleHarvestLogin} />
        </div>
        <div className="col-sm-6">
          <h2>Change Costlocker Account</h2>
          <CostlockerLogin clLoginError={clLoginError} loginUrl={loginUrl} />
        </div>
      </div>
    </div>
  );
};
