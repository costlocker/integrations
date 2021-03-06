import React from 'react';

import { Errors } from '../ui/Components';

export default function OAuthLogin({ title, loginError, loginUrl }) {
  return (
    <div>
      <Errors title="Login error" error={loginError} />
      <a href={loginUrl} className="btn btn-primary">{title}</a>
    </div>
  );
};
