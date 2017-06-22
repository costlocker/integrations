import React from 'react';

import Expenses from './Expenses';
import { CostlockerCosts } from './PeopleCosts';
import { FullButton } from '../Helpers';

export default function Summary({ project, goToNextStep }) {
  return (
    <div>
      <FullButton text="Import project to Costlocker" onClick={goToNextStep} />
      <h2>Personnel Costs</h2>
      <CostlockerCosts peopleCosts={project.peoplecosts} />
      <Expenses expenses={project.expenses} />
    </div>
  );
}
