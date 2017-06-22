import React from 'react';

export default function Expenses({ expenses, currencySymbol }) {
  return (
    <div>
      <h2>Project expenses</h2>
      <table className="table table-striped table-hover table-condensed">
        <thead>
          <tr>
            <th rowSpan="2">Expense</th>
            <th className="bg-warning" colSpan="2">Purchased</th>
            <th className="bg-info">Billed</th>
          </tr>
          <tr>
            <th className="bg-warning">Amount [{currencySymbol}]</th>
            <th className="bg-warning">Date</th>
            <th className="bg-info">Amount [{currencySymbol}]</th>
          </tr>
        </thead>
        <tbody>
          {expenses.map(expense => (
            <tr key={expense.id}>
              <th title={expense.id}>{expense.description}</th>
              <td>{expense.purchased.total_amount}</td>
              <td>{expense.purchased.date}</td>
              <td>{expense.billed.total_amount}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
