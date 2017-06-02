import React from 'react';

const AnonymousUser = () => <em>Not logged in</em>;

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
      <div className="text-primary"><CostlockerUser user={auth.costlocker} /></div>
      <div className="text-success"><AnonymousUser /></div>
    </div>
  );
};

export { CostlockerUser, User }
