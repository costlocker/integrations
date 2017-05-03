import React from 'react';

export default function User({ harvestUser }) {
  return (
    <div>
      <img src={harvestUser.user_avatar} alt="" width="25px" />
      <strong>{harvestUser.user_name}</strong>
      (<a href={harvestUser.company_url}>{harvestUser.company_name}</a>)
    </div>
  );
};
