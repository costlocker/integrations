import React from 'react';

const BillingAggregation = ({ billing }) => (
  <table className="table table-striped table-hover table-condensed">
    <thead>
      <tr>
        <th>Issued Amount [$]</th>
        <th>Invoiced Amount [$]</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>{billing.stats.issued}</td>
        <td>{billing.stats.invoiced}</td>
      </tr>
    </tbody>
  </table>
);

const Billing = ({ billing }) => {
  return (
    <div>
      <h2>Billing</h2>
      <p>
        Project invoices can't be loaded from <a href="http://help.getharvest.com/api-v1/invoices-api/">API</a>.
        So we load client invoices and aggregate them to issued/invoiced number.
        Numbers could be wrong, take it as guess. You should check that the numbers are OK before starting import to Costlocker.
      </p>
      <BillingAggregation billing={billing} />

      <h3>Invoices</h3>
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
          {billing.invoices.map(bill => (
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

export { Billing, BillingAggregation }
