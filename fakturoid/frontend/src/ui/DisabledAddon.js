import React from 'react';
import { CostlockerLink, Link } from './Components';
import { Image } from './Images';
import { pushToApi } from '../api';
import { trans } from '../i18n';

const reloadPage = () => window.location.reload(false);

const logoutUser = () => {
  pushToApi('/logout', {})
    .catch(reloadPage)
    .then(reloadPage)
};

export default function DisabledAddon({ user }) {
  const company = user ? `"${user.company.name}"` : '';
  return (
    <div className="container text-center">
      <div className="row">
        <div className="col-sm-6 col-sm-offset-3">
          <div className="panel panel-danger error-page">
            <div className="panel-heading">
              <h1 className="panel-title">{trans('disabledAddon.title')}</h1>
            </div>
            <div className="panel-body">
              {trans('disabledAddon.ask')} <CostlockerLink path="/settings/addons" title={trans('disabledAddon.enable')} />.
              <br /><br />
              <Image className="img-responsive" src="https://user-images.githubusercontent.com/7994022/27791571-fd97c17e-5ff5-11e7-9690-05715f1d3742.png" />
            </div>
            <div className="panel-footer">
              <Link
                title={trans('disabledAddon.logout', { company: company })}
                action={logoutUser}
                className="btn btn-default" />
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
