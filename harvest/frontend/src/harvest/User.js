import React from 'react';

export default function User({ harvestUser }) {
  return (
    <div>
      <img src={harvestUser.get('user_avatar')} alt="" width="25px" />
      <strong>{harvestUser.get('user_name')}</strong>
      (<a href={harvestUser.get('company_url')}>{harvestUser.get('company_name')}</a>)
    </div>
  );
};
