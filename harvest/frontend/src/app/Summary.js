import React from 'react';

import Expenses from './Expenses';
import { CostlockerCosts } from './PeopleCosts';
import { FullButton } from '../ui/Components';

const calculateRevenue = (project) => {
  let revenue = 0;
  project.peoplecosts.tasks.forEach(task => revenue += task.finance.revenue);
  project.expenses.forEach(expense => revenue += expense.billed.total_amount);
  return revenue;
}

export default function Summary({ project, goToNextStep }) {
  const revenue = calculateRevenue(project);
  const currencySymbol = project.selectedProject.client.currency;
  return (
    <div>
      <FullButton text="Import project to Costlocker" onClick={goToNextStep} />
      <h2>Overview</h2>
      <table className="table table-striped table-condensed">
        <thead>
          <tr>
            <th>Metric</th>
            <th>Amount [{currencySymbol}]</th>
            <th>Note</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <th>Revenue</th>
            <td>{revenue}</td>
            <td className="text-muted">
              Personnel costs (money budget, or billable amount for hourly budgeted projects) + billable expenses
            </td>
          </tr>
          <tr>
            <th>Billing</th>
            <td>{revenue}</td>
            <td className="text-muted">
              We use revenue, because project invoices can't be loaded from <a href="http://help.getharvest.com/api-v1/invoices-api/">API</a>.
            </td>
          </tr>
        </tbody>
      </table>
      <h2>Personnel Costs</h2>
      <CostlockerCosts peopleCosts={project.peoplecosts} />
      <Expenses expenses={project.expenses} currencySymbol={currencySymbol} />
    </div>
  );
}
