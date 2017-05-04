import React from 'react';

export default function CostlockerLogin({ clLoginError, loginUrl }) {
  return (
    <div>
      {clLoginError ? <p className="bg-danger"><strong>Login error</strong>:<br />{decodeURIComponent(clLoginError)}</p> : ''}
      <a href={loginUrl} className="btn btn-primary">Login to Costlocker</a>
    </div>
  );
};
