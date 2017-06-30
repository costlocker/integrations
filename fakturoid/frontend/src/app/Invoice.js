import React from 'react';
import { Map } from 'immutable';
import { Button, Link, Errors } from '../ui/Components';

const InvoiceDetail = ({ costlockerInvoice }) => (
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
        <td>{costlockerInvoice.billing.billing.description}</td>
        <td>
          {costlockerInvoice.project.name} <span className="badge">{costlockerInvoice.project.client.name}</span>
          <br />
          <span className="text-muted">{costlockerInvoice.project.project_id.id}</span>
        </td>
        <td>{costlockerInvoice.billing.billing.date}</td>
        <td>{costlockerInvoice.billing.billing.total_amount}</td>
      </tr>
    </tbody>
  </table>
);

const InvoiceEditor = ({ fakturoidSubjects, costlockerInvoice, form, invoiceCursor }) => {
  const lines = invoiceCursor.get('lines').deref();
  if (!lines.size) {
    invoiceCursor.get('lines').update(list => list.push(Map({
      name: costlockerInvoice.billing.billing.description
        ? costlockerInvoice.billing.billing.description
        : costlockerInvoice.project.name,
      amount: costlockerInvoice.billing.billing.total_amount,
    })));
  }
  const changeLine = (field, index, e) => {
    e.preventDefault();
    invoiceCursor.get('lines').update(list => list.update(
      index,
      value => value.set(field, e.target.value)
    ));
  }

  return <form className="form" onSubmit={form.submit}>
    <div className="form-group">
      <label htmlFor="fakturoidSubject">Fakturoid subject</label>
      <div className="row">
        <div className="col-sm-10">
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
        <div className="col-sm-2">
          <Link route='createSubject' title="Create a new subject" className="btn btn-default btn-block" />
        </div>
      </div>
    </div>
    <h3>Invoice lines</h3>
    <table className="table">
      <thead>
        <tr>
          <th>Name</th>
          <th>Total Amount</th>
          <th width="10">Quantity</th>
        </tr>
      </thead>
      <tbody>
        {lines.map((line, index) => (
          <tr key={index}>
            <td>
              <input
                className="form-control" required
                value={line.get('name')} onChange={e => changeLine('name', index, e)}
              />
            </td>
            <td>
              <input
                className="form-control" required size="10"
                value={line.get('amount')} onChange={e => changeLine('amount', index, e)}
              />
            </td>
            <td>
              <input className="form-control" type="text" disabled value="1ks" />
            </td>
          </tr>
        ))}
      </tbody>
    </table>
    <button type="submit" className="btn btn-primary btn-block">Create invoice</button>
  </form>;
}

export default function Invoice(props) {
  const costlockerInvoice = props.costlockerInvoice;
  if (
    costlockerInvoice.status === 'READY' ||
    (costlockerInvoice.status === 'ALREADY_IMPORTED' && props.form.get('isForced'))
  ) {
    return <div>
      <h3>Costlocker billing</h3>
      <InvoiceDetail costlockerInvoice={costlockerInvoice} />
      <InvoiceEditor {...props} />
    </div>;
  } else if (costlockerInvoice.status === 'NOT_DRAFT') {
    return <div>
      <h3>Costlocker billing</h3>
      <InvoiceDetail costlockerInvoice={costlockerInvoice} />
      <Errors title="Invalid invoice state" error="Billing is already invoiced in Costlocker" />
    </div>;
  } else if (costlockerInvoice.status === 'ALREADY_IMPORTED') {
    return <div>
      <h3>Costlocker billing</h3>
      <InvoiceDetail costlockerInvoice={costlockerInvoice} />
      <div className="row">
        <div className="col-sm-6 text-left">
          <a href={costlockerInvoice.invoice.link} className="btn btn-success" target="_blank" rel="noopener noreferrer">
            {`Open invoice #${costlockerInvoice.invoice.number} in Fakturoid`}
          </a>
        </div>
        <div className="col-sm-6 text-right">
          <Button
            title="Create invoice once again"
            className="btn btn-warning"
            action={props.forceUpdate}
          />
        </div>
      </div>
    </div>;
  }
  return <Errors title="Unknown billing" error="Billing not found in Costlocker" />;
}
