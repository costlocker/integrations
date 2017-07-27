import React from 'react';
import { Button, Link, Errors, roundNumber, Number, ExternalLink, FakturoidLink } from '../ui/Components';
import InvoicesList from './InvoicesList';
import { PageWithSubnav, Page } from '../ui/App';
import { isDevelopmentMode } from '../config';
import { CenteredModal } from '../ui/Modals';
import { appState } from '../state';

const InvoiceDetail = ({ costlocker }) => (
  <div>
    <h1>
      New Invoice
      {costlocker.project.project_id.id ? <span> / {costlocker.project.project_id.id}</span> : null}
    </h1>
    <p>
      <strong>{costlocker.project.name}</strong>, <span>{costlocker.project.client.name}</span>
    </p>
  </div>
);

const loadVat = (subjects, form) => {
  let hasVat = false;
  subjects.forEach(s => {
    if (s.id == form.get('subject')) {
      hasVat = s.has_vat;
    }
  })
  form.set('hasVat')({
    target: {
      type: 'checkbox',
      checked: hasVat,
    }
  });
};

const AddLinesModal = ({ title, activityTabs }) => {
  const hasMultipleTabs =  activityTabs.length > 1;
  const isActive = type => hasMultipleTabs
    ? type.id === (appState.cursor(['invoiceModal', 'activeTab']).deref() || activityTabs[0].id)
    : true;
  const changeTab = (e) => appState.cursor(['invoiceModal']).set('activeTab', e.target.value);

  const checkItem = (e) => appState.cursor(['invoiceModal', 'checkedIds']).update(
    set => e.target.checked ? set.add(e.target.value) : set.delete(e.target.value)
  );
  const isChecked = item => appState.cursor(['invoiceModal', 'checkedIds']).deref().contains(item.id);
  const checkAll = items => () => appState.cursor(['invoiceModal', 'checkedIds']).update(
    set => {
      let updated = set;
      items.forEach(item => {
        updated = updated.add(item.id);
      })
      return updated;
    }
  );
  const initModal = () => appState.cursor(['invoiceModal']).update(
    modal => modal
      .setIn(['activeTab'], '')
      .setIn(['checkedIds'], modal.get('checkedIds').clear())
  );
  return <CenteredModal
    type={title}
    onOpen={initModal}
    link={{
      title:  title,
      className: "btn btn-primary",
    }}
    content={
      (closeModal) =>
        <div>
          <h2>{title}</h2>
          {hasMultipleTabs ? (
          <div className="btn-group btn-group-justified">
            {activityTabs.map(type => (
              <label key={type.id} className={isActive(type) ? 'btn btn-primary active' : 'btn btn-default'}>
                <input
                  type="radio" name="type" value={type.id} className="hide"
                  checked={isActive(type)} onChange={changeTab} /> {type.title}
              </label>
            ))}
          </div>
          ) : null}
          {activityTabs.map(type => {
            const items = type.items();
            return <div key={type.id} className={isActive(type) ? 'show' : 'hide'}>
              <form className="form">
                {items.map(item => (
                  <label key={item.id} className="checkbox">
                    <input
                      type="checkbox"
                      checked={isChecked(item)} onChange={checkItem} value={item.id}
                    />
                    {item.name}
                  </label>
                ))}
                <Link title={`Select all (${items.length})`} action={checkAll(items)} className="btn btn-link" />
                <Button
                  title="Add selected"
                  action={() => {
                    type.action();
                    closeModal();
                  }}
                  className="btn btn-success btn-block"
                />
              </form>
            </div>;
          })}
        </div>
    }
  />;
}

const InvoiceEditor = ({ fakturoidSubjects, costlocker, form, lines, reloadSubjects }) => {
  lines.addDefaultIfIsEmpty({
    name: costlocker.billing.billing.description
      ? costlocker.billing.billing.description
      : costlocker.project.name,
    amount: costlocker.billing.billing.total_amount
  })

  const linesAmount = lines.calculateTotaAmount();
  const billedAmount = costlocker.billing.billing.total_amount;
  loadVat(fakturoidSubjects, form);

  const activityTabs = {
    people: {
      id: 'people',
      title: "People",
      items: () => {
        const people = costlocker.project.budget.peoplecosts.map(activityCost => (
            activityCost.people.map(personCost => (JSON.stringify({
              id: `person-${personCost.item.person_id}`,
              type: 'people',
              name: `${personCost.person.first_name} ${personCost.person.last_name}`,
            })))
          ));
        //
        return [].concat.apply([], people) // reduce 2d arrays
          .filter((value, index, self) => self.indexOf(value) === index) // unique
          .map(JSON.parse); // convert to object
      },
      actions: () => lines.addPeople(costlocker.project.budget.peoplecosts)(),
    },
    activities: {
      id: 'activities',
      title: "Activities",
      items: () => costlocker.project.budget.peoplecosts.map(
        activityCost => ({
          id: `activity-${activityCost.item.activity_id}`,
          type: 'activities',
          name: activityCost.activity.name,
        })
      ),
      action: () => lines.addActivities(costlocker.project.budget.peoplecosts)(),
    },
    expenses: {
      id: 'expenses',
      title: "Expenses",
      items: () => costlocker.project.budget.expenses.map(
        expense => ({
          id: `expense-${expense.item.expense_id}`,
          type: 'expenses',
          name: expense.expense.description,
        })
      ),
      action: () => lines.addActivities(costlocker.project.budget.expenses)(),
    },
  };
  const getActiveTabs = visibleTabs => visibleTabs.map(id => activityTabs[id]);

  return <form className="form" onSubmit={form.submit}>
    <div>
      <input
        className="form-control" type="hidden" id="vat" min="0" max="100" step="1"
        value={form.get('vat')} required
      />
    </div>
    <div className="form-group">
      <label htmlFor="type">Invoice type</label><br />
      <div className="btn-group">
        {[
          { id: 'invoice', title: 'Invoice' },
          { id: 'proforma.full', title: 'Proforma (full)' },
          { id: 'proforma.partial', title: 'Proforma (partial)' },
        ].map(type => (
          <label key={type.id} className={form.get('type') === type.id ? 'btn btn-primary active' : 'btn btn-default'}>
            <input
              type="radio" name="type" value={type.id} className="hide"
              checked={form.get('type') === type.id} onChange={form.set('type')} /> {type.title}
          </label>
        ))}
      </div>
    </div>
    <div className="form-group">
      <label htmlFor="fakturoidSubject">Fakturoid client</label>
      <div className="row">
        <div className="col-sm-8">
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
        <div className="col-sm-4">
          <FakturoidLink title="Add a new Fakturoid client" path="/subjects/new" className="btn btn-success btn-block" />
        </div>
      </div>
      <p className="help-block">
        Can't see a specific client? <Link
          action={reloadSubjects}
          title={<span><i className="fa fa-refresh" /> Try refreshing the list</span>}
          className="btn btn-link"
        />
      </p>
    </div>
    <div className="row">
      <div className="col-sm-3">
        <div className="form-group">
          <label htmlFor="issuedAt">Issued at</label>
          <input
            type="text" className="form-control" name="issuedAt" id="issuedAt"
            value={form.get('issuedAt')} onChange={form.set('issuedAt')}
          />
        </div>
      </div>
      <div className="col-sm-3">
        <div className="form-group">
          <label htmlFor="due">Due</label>
          <input
            className="form-control" type="number" id="due" min="1" max="100" step="1"
            value={form.get('due')} onChange={form.set('due')}
          />
        </div>
      </div>
    </div>
    <div className="form-group">
      <label htmlFor="orderNumber">Order Number</label>
      <input
        type="text" className="form-control" name="orderNumber" id="orderNumber"
        value={form.get('orderNumber')} onChange={form.set('orderNumber')}
      />
    </div>
    <div className="form-group">
      <label htmlFor="noteBeforeLines">Note before invoice lines</label>
      <textarea
        className="form-control" name="noteBeforeLines" id="noteBeforeLines" rows="4"
        value={form.get('noteBeforeLines')} onChange={form.set('noteBeforeLines')}
      >
      </textarea>
    </div>
    <div className="form-group">
      <div className="row">
        <div className="col-sm-10">
          <div className="btn-toolbar">
            <div className="btn-group">
              <AddLinesModal title="Add people or activities" activityTabs={getActiveTabs(['people', 'activities'])} />
            </div>
            <div className="btn-group">
              <AddLinesModal title="Add expenses" activityTabs={getActiveTabs(['expenses'])} />
            </div>
            <div className="btn-group">
              <Link
                title="Add discount" action={lines.addDiscount(costlocker.project.budget.discount)}
                className={`btn btn-primary ${costlocker.project.budget.discount ? '' : 'disabled'}`} />
            </div>
          </div>
        </div>
        <div className="col-sm-2">
          <div className="btn-toolbar">
            <div className="btn-group-justified">
              <Link title="Reset lines" action={lines.removeAllLines()} className="btn btn-default" />
            </div>
          </div>
        </div>
      </div>
      <table className="table">
        <thead>
          <tr>
            <th width="100">Quantity</th>
            <th width="100">Unit</th>
            <th>Name</th>
            <th>Price per unit</th>
            <th>Total price</th>
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
                  className="form-control" type="text" required
                  value={line.get('name')} onChange={lines.updateFieldInLine('name', line)}
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
        {form.get('hasVat') ? (
          <tfoot>
            <tr>
              <td colSpan="6">
                <Link title="Add new empty line" action={lines.addEmptyLine()} className="btn btn-success" />
              </td>
            </tr>
            <tr>
              <th colSpan="4" className="text-right">Total amount (without VAT)</th>
              <th colSpan="2"><Number value={linesAmount} isElement /></th>
            </tr>
            <tr>
              <th colSpan="4" className="text-right">VAT</th>
              <th colSpan="2"><Number value={form.get('vat')} isElement />%</th>
            </tr>
            <tr>
              <th colSpan="4" className="text-right">Total amount (with VAT)</th>
              <th colSpan="2"><Number value={linesAmount + linesAmount * (form.get('vat')) / 100} isElement /></th>
            </tr>
          </tfoot>
        ) : (
            <tfoot>
              <tr>
                <th colSpan="4" className="text-right">Total amount</th>
                <th colSpan="2"><Number value={linesAmount} isElement /></th>
              </tr>
            </tfoot>
          )}
      </table>
    </div>
    {Math.abs(billedAmount - linesAmount) <= 0.1 ? (
      <div className="row">
        <div className="col-sm-4 col-sm-offset-8">
          <button type="submit" className="btn btn-primary btn-block">Create Invoice</button>
        </div>
      </div>
    ) : (
      <Errors
        title={`Billed amount '${roundNumber(billedAmount)}' in Costlocker is different than total amount in invoice lines '${roundNumber(linesAmount)}'`}
        error="Update quantity or unit amount in lines, so that amount is same"
      />
      )}
    <div className="form-group">
      <label htmlFor="note">Note</label>
      <textarea
        className="form-control" name="note" id="note" rows="4"
        placeholder="Add private note to invoice..."
        value={form.get('note')} onChange={form.set('note')}
      >
      </textarea>
    </div>
  </form>;
}

export default function Invoice(props) {
  const invoice = props.invoice;
  const buildSubnav = (content) => {
    return <PageWithSubnav
      tabs={[
        {
          id: 'invoice',
          name: 'New invoice',
          content: () => <div className="row">
            <div className="col-sm-10 col-sm-offset-1">
              <InvoiceDetail costlocker={props.invoice.costlocker} />
              {content}
            </div>
          </div>,
        },
        {
          id: 'project',
          name: 'Previously imported project invoices',
          content: () => <InvoicesList invoices={props.invoices} subjects={props.fakturoidSubjects} />,
        },
      ]}
    />;
  }
  if (
    invoice.status === 'READY' || invoice.status === 'NEW' ||
    (invoice.status === 'ALREADY_IMPORTED' && props.form.get('isForced'))
  ) {
    return buildSubnav(<InvoiceEditor {...props} costlocker={props.invoice.costlocker} />);
  } else if (invoice.status === 'NOT_DRAFT') {
    return buildSubnav(<Errors title="Invalid invoice state" error="Billing is already invoiced in Costlocker" />);
  } else if (invoice.status === 'ALREADY_IMPORTED') {
    return buildSubnav(<div className="row">
      <div className="col-sm-6 text-left">
        <ExternalLink url={invoice.fakturoid.link} title={`Open invoice #${invoice.fakturoid.number} in Fakturoid`} className="btn btn-success" />
      </div>
      <div className="col-sm-6 text-right">
        {isDevelopmentMode &&
        <Button
          title="Create invoice once again"
          className="btn btn-warning"
          action={props.forceUpdate}
        />
        }
      </div>
    </div>);
  }
  return <Page
    view={<Errors title="Unknown billing" error="Billing not found in Costlocker, or you aren't authorized to see the project" />}
  />;
}
