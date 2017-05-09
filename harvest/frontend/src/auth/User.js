import React from 'react';

import { ExternalLink } from '../Helpers';

const AnonymousUser = () => <em>Not logged in</em>;

const HarvestUser = ({ user }) => {
  if (!user) {
    return <AnonymousUser />
  }
  return (
    <div>
      <strong>
        {user.user_name}
      </strong> ({user.company_name} <ExternalLink url={user.company_url} />)
    </div>
  );
};

const CostlockerUser = ({ user }) => {
  if (!user) {
    return <AnonymousUser />
  }
  return (
    <div>
      <strong>
        {user.person.first_name} {user.person.last_name}
      </strong> ({user.company.name})
    </div>
  );
};

const User = ({ auth }) => {
  return (
    <div>
      <div className="text-warning"><HarvestUser user={auth.harvest} /></div>
      <div className="text-primary"><CostlockerUser user={auth.costlocker} /></div>
    </div>
  );
};

export { HarvestUser, CostlockerUser, User }
