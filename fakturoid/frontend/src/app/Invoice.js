import React from 'react';
import { Button, Link, Errors, roundNumber, Number } from '../ui/Components';
import InvoicesList from './InvoicesList';
import { PageWithSubnav } from '../ui/App';

const InvoiceDetail = ({ costlockerInvoice }) => (
  <table className="table">
    <thead>
      <tr>
        <th style={{ width: '50%' }}>Bill ID / Description</th>
        <th>Client</th>
        <th>Project</th>
        <th>Billing Date</th>
        <th>Amount</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>{costlockerInvoice.billing.billing.description}</td>
        <td>{costlockerInvoice.project.client.name}</td>
        <td>
          {costlockerInvoice.project.name} <span className="badge">{costlockerInvoice.project.project_id.id}</span>
        </td>
        <td>{costlockerInvoice.billing.billing.date}</td>
        <td>{costlockerInvoice.billing.billing.total_amount}</td>
      </tr>
    </tbody>
  </table>
);

const InvoiceEditor = ({ fakturoidSubjects, costlockerInvoice, form, lines, reloadSubjects }) => {
  lines.addDefaultIfIsEmpty({
    name: costlockerInvoice.billing.billing.description
        ? costlockerInvoice.billing.billing.description
        : costlockerInvoice.project.name,
    amount: costlockerInvoice.billing.billing.total_amount
  })

  const linesAmount = lines.calculateTotaAmount();
  const billedAmount = costlockerInvoice.billing.billing.total_amount;

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
      <p className="help-block text-right">
        Are subjects out of date? <Link action={reloadSubjects} title="Download subjects from Fakturoid" className="text-danger" />
      </p>
    </div>
    <h3>Invoice lines</h3>
    <div className="btn-toolbar">
      <div className="btn-group">
        <Link title="Add activities" action={lines.addActivities(costlockerInvoice.project.budget.peoplecosts)} className="btn btn-default" />
        <Link title="Add people" action={lines.addPeople(costlockerInvoice.project.budget.peoplecosts)} className="btn btn-default" />
      </div>
      <div className="btn-group">
        <Link title="Add expenses" action={lines.addExpenses(costlockerInvoice.project.budget.expenses)} className="btn btn-default" />
        <Link
           title="Add discount" action={lines.addDiscount(costlockerInvoice.project.budget.discount)}
           className={`btn btn-default ${costlockerInvoice.project.budget.discount ? '' : 'disabled'}`} />
      </div>
      <div className="btn-group">
        <Link title="Add empty line" action={lines.addEmptyLine()} className="btn btn-default" />
        <Link title="Reset lines" action={lines.removeAllLines()} className="btn btn-default" />
      </div>
    </div>
    <table className="table">
      <thead>
        <tr>
          <th>Name</th>
          <th width="100">Quantity</th>
          <th width="100">Unit</th>
          <th>Unit Amount</th>
          <th>Total Amount</th>
          <th width="10"></th>
        </tr>
      </thead>
      <tbody>
        {lines.map((line) => {
          const isLineIgnored = line.get('quantity') <= 0;
          return <tr
            key={line.get('id')}
            className={isLineIgnored ? 'bg-danger' : null}
            title={isLineIgnored ? 'Line will be ignored and not imported to Fakturoid' : null}
          >
            <td>
              <input
                className="form-control" type="text" required
                value={line.get('name')} onChange={lines.updateFieldInLine('name', line)}
              />
            </td>
            <td>
              <input
                className="form-control" type="number" step="any" required
                value={line.get('quantity')} onChange={lines.updateFieldInLine('quantity', line)}
              />
            </td>
            <td>
              <input
                className="form-control" type="text"
                value={line.get('unit')} onChange={lines.updateFieldInLine('unit', line)}
              />
            </td>
            <td>
              <input
                className="form-control" type="number" step="any" required size="10"
                value={roundNumber(line.get('unit_amount'))} onChange={lines.updateFieldInLine('unit_amount', line)}
              />
            </td>
            <td>
              <input
                className="form-control" type="number" step="any" required
                disabled value={roundNumber(line.get('total_amount'))}
              />
            </td>
            <td>
              {lines.hasMultipleLines() ? (
              <Link
                title={<span className="fa fa-trash"></span>} className="btn btn-link text-danger"
                action={lines.removeLine(line)}
              />
              ) : <span className="btn btn-link disabled fa fa-trash"></span>}
            </td>
          </tr>;
        })}
      </tbody>
      <tfoot>
        <tr>
          <th colSpan="4" className="text-right">Total amount</th>
          <th><Number value={linesAmount} isElement /></th>
        </tr>
      </tfoot>
    </table>
    <div className="form-group">
      <label htmlFor="note">Note</label>
      <textarea
        className="form-control" name="note" id="note"
        placeholder="Add private note to invoice..."
        value={form.get('note')} onChange={form.set('note')}
      >
      </textarea>
    </div>
    {Math.abs(billedAmount - linesAmount) <= 0.1 ? (
      <button type="submit" className="btn btn-primary btn-block">Create invoice</button>
    ) : (
      <Errors
        title={`Billed amount '${roundNumber(billedAmount)}' in Costlocker is different than total amount in invoice lines '${roundNumber(linesAmount)}'`}
        error="Update quantity or unit amount in lines, so that amount is same"
      />
    )}
  </form>;
}

export default function Invoice(props) {
  const costlockerInvoice = props.costlockerInvoice;
  const buildSubnav = (content) => {
    return <PageWithSubnav
      tabs={[
        {
          id: 'invoice',
          name: 'New invoice',
          content: () => <div>
            <h3>Costlocker billing</h3>
            <InvoiceDetail costlockerInvoice={costlockerInvoice} />
            {content}
          </div>,
        },
        {
          id: 'project',
          name: 'Previously imported project invoices',
          content: () => <InvoicesList invoices={props.projectInvoices} subjects={props.fakturoidSubjects} />,
        },
      ]}
    />;
  }
  if (
    costlockerInvoice.status === 'READY' ||
    (costlockerInvoice.status === 'ALREADY_IMPORTED' && props.form.get('isForced'))
  ) {
    return buildSubnav(<InvoiceEditor {...props} />);
  } else if (costlockerInvoice.status === 'NOT_DRAFT') {
    return buildSubnav(<Errors title="Invalid invoice state" error="Billing is already invoiced in Costlocker" />);
  } else if (costlockerInvoice.status === 'ALREADY_IMPORTED') {
    return buildSubnav(<div className="row">
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
    </div>);
  }
  return <Errors title="Unknown billing" error="Billing not found in Costlocker" />;
}
