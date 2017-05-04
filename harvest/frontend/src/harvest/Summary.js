import React from 'react';

import Expenses from './Expenses';
import { BillingAggregation } from './Billing';
import { CostlockerCosts } from './PeopleCosts';

export default function Summary({ project }) {
  return (
    <div>
      <h2>People Costs</h2>
      <CostlockerCosts peopleCosts={project.peoplecosts} />
      <Expenses expenses={project.expenses} />
      <h2>Billing</h2>
      <BillingAggregation billing={project.billing} />
    </div>
  );
}
