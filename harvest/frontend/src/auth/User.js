import React from 'react';

const AnonymousUser = () => <em>Not logged in</em>;

const HarvestUser = ({ user }) => {
  if (!user) {
    return <AnonymousUser />
  }
  return (
    <div>
      <img src={user.user_avatar} alt="" width="25px" />
      <strong>{user.user_name}</strong>
      (<a href={user.company_url}>{user.company_name}</a>)
    </div>
  );
};

const CostlockerUser = ({ user }) => {
  if (!user) {
    return <AnonymousUser />
  }
  return (
    <strong>
      {user.access_token}
    </strong>
  );
};

const User = ({ auth, isFirstStep }) => {
  if (isFirstStep) {
    return null;
  }
  return (
    <div>
      <HarvestUser user={auth.harvest} />
      <CostlockerUser user={auth.costlocker} />
    </div>
  );
};

export { HarvestUser, CostlockerUser, User }
