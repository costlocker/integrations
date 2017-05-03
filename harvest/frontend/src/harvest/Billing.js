import React from 'react';

export default function Billing({ billing }) {
  return (
    <div>
      <h2>Billing</h2>
      <p></p>
      <table className="table table-striped table-hover table-condensed">
        <thead>
          <tr>
            <th>Invoice</th>
            <th>Date</th>
            <th>Amount [$]</th>
            <th>Is invoiced?</th>
          </tr>
        </thead>
        <tbody>
          {billing.map(bill => (
            <tr key={bill.id}>
              <th title={bill.id}>{bill.description}</th>
              <td>{bill.date}</td>
              <td>{bill.total_amount}</td>
              <td>{bill.is_invoiced ? 'yes' : 'no'}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
