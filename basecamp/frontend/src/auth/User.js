import React from 'react';

const AnonymousUser = () => <em>Not logged in</em>;

const CostlockerUser = ({ user, redirectToRoute }) => {
  if (!user) {
    return <AnonymousUser />;
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

const BasecampUser = ({ user, redirectToRoute }) => {
  let userInfo = <AnonymousUser />;
  if (user) {
    userInfo = <span>
      {user.identity.first_name} {user.identity.last_name} ({user.identity.email_address})
    </span>;
  }
  return (
    <div>
      {userInfo}
      &nbsp;<button onClick={() => redirectToRoute('basecamp')} className="btn btn-default btn-sm">
          Basecamp accounts
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
      <div className="text-success">
        <BasecampUser user={auth.basecamp} redirectToRoute={redirectToRoute} />
      </div>
    </div>
  );
};

export { CostlockerUser, User }
