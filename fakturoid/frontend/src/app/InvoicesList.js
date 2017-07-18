import React from 'react';
import { ExternalLink, roundNumber } from '../ui/Components';
import { appState } from '../state';

const isHighlighted = id => id === appState.cursor(['app', 'lastCreatedInvoice']).deref();

export default function InvoicesList({ invoices, subjects }) {
  if (!invoices.length) {
    return <p className="text-muted">No invoices were created</p>;
  }
  const indexedSubjects = [];
  if (subjects) {
    subjects.forEach(s => indexedSubjects[s.id] = s.name);
  }
  const lines = [];
  invoices.forEach((invoice) => {
    lines.push(
      <tr key={invoice.id} className={isHighlighted(invoice.id) ? 'highlight' : ''}>
        <td>{invoice.date}</td>
        <td>
          <span className="text-primary">{invoice.costlocker.user}</span><br />
          {invoice.fakturoid.user}
        </td>
        <td>
          <span className="text-primary">{invoice.costlocker.project.client.name}</span><br />
          {indexedSubjects[invoice.fakturoid.subject] ? indexedSubjects[invoice.fakturoid.subject] : `#${invoice.fakturoid.subject}`}
        </td>
        <td>
          <span className="text-primary">
            #{invoice.costlocker.billing.item.billing_id}
            &nbsp;{invoice.costlocker.project.name}
            &nbsp;<span className="badge">{invoice.costlocker.project.project_id.id}</span>
            &nbsp;<ExternalLink url={invoice.costlocker.link} />
          </span><br />
          {invoice.fakturoid.number} <span className="badge">{invoice.fakturoid.type}</span> <ExternalLink url={invoice.fakturoid.link} />
        </td>
      </tr>
    );
    const invoiceLines = [];
    invoice.fakturoid.lines.forEach((line, index) => {
      invoiceLines.push(
        <tr key={`${invoice.id}-${index}`}>
          <td>{line.name}</td>
          <td width="120">{line.quantity}{line.unit}</td>
          <td width="120">{roundNumber(line.unit_amount)}</td>
          <td width="120">{roundNumber(line.total_amount)}</td>
          <td width="120">{line.vat ? line.vat : 0}</td>
        </tr>
      );
    })
    invoiceLines.push(
      <tr key={`${invoice.id}-total`}>
        <td colSpan="3"></td>
        <th>{invoice.costlocker.billing.billing.total_amount}</th>
        <th>{roundNumber(invoice.fakturoid.amount)}</th>
      </tr>
    );
    lines.push(
      <tr key={`${invoice.id}-lines`} className={isHighlighted(invoice.id) ? 'highlight-light' : ''}>
        <td className="text-right">
          <em>Invoice lines</em><br />
          {invoice.fakturoid.template.actions.map(action => (
            <span key={action}>
              <span className="label label-primary">{action}</span>
              <br />
            </span>
          ))}
        </td>
        <td colSpan="3">
          <table className="table table-striped table-condensed table-bordered">
            <thead>
              <tr>
                <th>Name</th>
                <th>Quantity</th>
                <th>Unit Amount</th>
                <th>Total Amount</th>
                <th>VAT</th>
              </tr>
            </thead>
            <tbody>{invoiceLines}</tbody>
          </table>
          <small className="text-muted">{invoice.fakturoid.note}</small>
        </td>
      </tr>
    );
    if (invoice.costlocker.update.hasFailed) {
      lines.push(
        <tr key={`${invoice.id}-error`} className={isHighlighted(invoice.id) ? 'bg-danger' : ''}>
          <td className="text-right">
            <em>Costlocker error</em>
          </td>
          <td colSpan="3">
            Costlocker billing wasn't update because:<br />
            <em className="text-danger">{invoice.costlocker.update.error}</em>
          </td>
        </tr>
      );
    }
  });
  return <table className="table table-striped">
    <thead>
      <tr>
        <th width="150">Date</th>
        <th>User</th>
        <th>Client / Customer</th>
        <th>Project / Invoice</th>
      </tr>
    </thead>
    <tbody>
      {lines}
    </tbody>
  </table>;
}
