import React from 'react';
import Loading from '../ui/Loading';
import { Errors } from '../ui/Components';

export default function Invoice({ fakturoidSubjects, costlockerInvoice, form }) {
  if (!fakturoidSubjects || !costlockerInvoice) {
    return <Loading title="Loading fakturoid clients, Costlocker invoice" />;
  }
  if (!costlockerInvoice.invoice) {
    return <Errors title="Unknown invoice" error="Invoice not found in Costlocker" />;
  }
  return <form className="form" onSubmit={form.submit}>
    <h3>Costlocker billing</h3>
    <table className="table">
      <thead>
        <tr>
          <th style={{ width: '50%' }}>Bill ID / Description</th>
          <th>Project / Client</th>
          <th>Billing Date</th>
          <th>Amount</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>{costlockerInvoice.invoice.billing.description}</td>
          <td>
            {costlockerInvoice.project.name} <span className="badge">{costlockerInvoice.project.client.name}</span>
            <br />
            <span className="text-muted">{costlockerInvoice.project.project_id.id}</span>
          </td>
          <td>{costlockerInvoice.invoice.billing.date}</td>
          <td>{costlockerInvoice.invoice.billing.total_amount}</td>
        </tr>
      </tbody>
    </table>
    <div className="form-group">
      <label htmlFor="fakturoidSubject">Fakturoid subject</label>
      <select required
        className="form-control" name="fakturoidSubject" id="fakturoidSubject"
        value={form.get('subject')} onChange={form.set('subject')}
      >
        <option></option>
        {fakturoidSubjects.map(subject => (
          <option key={subject.id} value={subject.id}>{subject.name}</option>
        ))}
      </select>
    </div>
    <button type="submit" className="btn btn-primary btn-block">Create invoice</button>
  </form>;
}
