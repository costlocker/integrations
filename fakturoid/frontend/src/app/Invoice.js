import React from 'react';
import { Map } from 'immutable';
import { Button, Link, Errors, roundNumber, Number } from '../ui/Components';

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

const InvoiceEditor = ({ fakturoidSubjects, costlockerInvoice, form, invoiceCursor, reloadSubjects }) => {
  const lines = invoiceCursor.get('lines').deref();
  const billedAmount = costlockerInvoice.billing.billing.total_amount;
  let linesAmount = lines.reduce((sum, item) => item.get('total_amount') + sum, 0);

  if (!lines.size) {
    invoiceCursor.get('lines').update(list => list.push(Map({
      name: costlockerInvoice.billing.billing.description
        ? costlockerInvoice.billing.billing.description
        : costlockerInvoice.project.name,
      quantity: 1,
      unit: 'ks',
      unit_amount: costlockerInvoice.billing.billing.total_amount,
      total_amount: costlockerInvoice.billing.billing.total_amount,
    })));
  }
  const addExpenses = (e) => {
    e.preventDefault();
    invoiceCursor.get('lines').update(list => {
      let updated = list;
      costlockerInvoice.project.budget.expenses.forEach(expense => {
        updated = updated.push(Map({
          name: expense.expense.description,
          quantity: 1,
          unit: 'ks',
          unit_amount: expense.expense.billed.total_amount,
          total_amount: expense.expense.billed.total_amount,
        }));
      });
      return updated;
    });
  };
  const addActivities = (e) => {
    e.preventDefault();
    invoiceCursor.get('lines').update(list => {
      let updated = list;
      costlockerInvoice.project.budget.peoplecosts.forEach(activityCost => {
        updated = updated.push(Map({
          name: activityCost.activity.name,
          quantity: activityCost.hours.budget,
          unit: 'h',
          unit_amount: activityCost.activity.hourly_rate,
          total_amount: activityCost.activity.hourly_rate * activityCost.hours.budget,
        }));
      });
      return updated;
    });
  };
  const addPeople = (e) => {
    e.preventDefault();
    invoiceCursor.get('lines').update(list => {
      let updated = list;
      costlockerInvoice.project.budget.peoplecosts.forEach(activityCost => {
        activityCost.people.forEach(personCost => {
          updated = updated.push(Map({
            name: `${activityCost.activity.name} - ${personCost.person.first_name} ${personCost.person.last_name}`,
            quantity: personCost.hours.budget,
            unit: 'h',
            unit_amount: activityCost.activity.hourly_rate,
            total_amount: activityCost.activity.hourly_rate * personCost.hours.budget,
          }));
        });
      });
      return updated;
    });
  };
  const addEmptyLine = (e) => {
    e.preventDefault();
    invoiceCursor.get('lines').update(list => list.push(Map({
      name: '',
      quantity: 0,
      unit: 'ks',
      unit_amount: 0,
      total_amount: 0,
    })));
  };
  const removeAll = (e) => {
    e.preventDefault();
    invoiceCursor.get('lines').update(list => list.clear());
  };

  const changeLine = (field, index, e) => {
    e.preventDefault();
    invoiceCursor.get('lines').update(list => list.update(
      index,
      value => {
        let updated = value.set(field, e.target.value);
        return updated.set('total_amount', updated.get('quantity') * updated.get('unit_amount'))
      }
    ));
  };

  const removeLine = (index, e) => {
    e.preventDefault();
    invoiceCursor.get('lines').update(list => list.delete(index));
  };

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
        <Link title="Add expenses" action={addExpenses} className="btn btn-default" />
        <Link title="Add activities" action={addActivities} className="btn btn-default" />
        <Link title="Add people" action={addPeople} className="btn btn-default" />
      </div>
      <div className="btn-group">
        <Link title="Add empty line" action={addEmptyLine} className="btn btn-default" />
        <Link title="Reset lines" action={removeAll} className="btn btn-default" />
      </div>
    </div>
    <table className="table">
      <thead>
        <tr>
          <th width="100">Quantity</th>
          <th width="100">Unit</th>
          <th>Name</th>
          <th>Unit Amount</th>
          <th>Total Amount</th>
          <th width="10"></th>
        </tr>
      </thead>
      <tbody>
        {lines.map((line, index) => {
          const isLineIgnored = line.get('quantity') <= 0;
          return <tr
            key={index}
            className={isLineIgnored ? 'bg-danger' : null}
            title={isLineIgnored ? 'Line will be ignored and not imported to Fakturoid' : null}
          >
            <td>
              <input
                className="form-control" type="number" step="any" required
                value={line.get('quantity')} onChange={e => changeLine('quantity', index, e)}
              />
            </td>
            <td>
              <input
                className="form-control" type="text"
                value={line.get('unit')} onChange={e => changeLine('unit', index, e)}
              />
            </td>
            <td>
              <input
                className="form-control" type="text" required
                value={line.get('name')} onChange={e => changeLine('name', index, e)}
              />
            </td>
            <td>
              <input
                className="form-control" type="number" step="any" required size="10"
                value={roundNumber(line.get('unit_amount'))} onChange={e => changeLine('unit_amount', index, e)}
              />
            </td>
            <td>
              <input
                className="form-control" type="number" step="any" required
                disabled value={roundNumber(line.get('total_amount'))}
              />
            </td>
            <td>
              {lines.size > 1 ? (
              <Link
                title={<span className="fa fa-trash"></span>} className="btn btn-link text-danger"
                action={(e) => removeLine(index, e)}
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
    {Math.abs(billedAmount - linesAmount) <= 0.1 ? (
      <button type="submit" className="btn btn-primary btn-block">Create invoice</button>
    ) : (
      <Errors
        title={`Billed amount '${billedAmount}' in Costlocker is different than total amount in invoice lines '${linesAmount}'`}
        error="Update quantity or unit amount in lines, so that amount is same"
      />
    )}
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
