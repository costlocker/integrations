import React from 'react';

const AnonymousUser = () => <em>Not logged in</em>;

const CostlockerUser = ({ user, redirectToRoute }) => {
  if (!user) {
    return <AnonymousUser />
  }
  return (
    <div>
      <strong>
        {user.person.first_name} {user.person.last_name}
      </strong> ({user.company.name})
      &nbsp;<button onClick={() => redirectToRoute('login')} className="btn btn-default btn-sm">
          Switch Costlocker account
      </button>
    </div>
  );
};

const User = ({ auth, redirectToRoute }) => {
  return (
    <div>
      <div className="text-primary">
        <CostlockerUser user={auth.costlocker} redirectToRoute={redirectToRoute} />
      </div>
      <div className="text-success"><AnonymousUser /></div>
    </div>
  );
};

export { CostlockerUser, User }
