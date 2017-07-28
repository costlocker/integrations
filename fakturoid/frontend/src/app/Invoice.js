import React from 'react';
import { Button, Link, Errors, roundNumber, Number, ExternalLink, FakturoidLink, CostlockerLink, Image } from '../ui/Components';
import InvoicesList from './InvoicesList';
import { PageWithSubnav } from '../ui/App';
import { isDevelopmentMode } from '../config';
import { CenteredModal } from '../ui/Modals';
import { appState } from '../state';
import billing from '../images/billing.png';

const InvoiceTutorial = ({ header }) =>
  <div className="text-center">
    <div className="row">
      <div className="col-sm-12">
        {header}
      </div>
    </div>
    <div className="row">
      <div className="col-sm-12">
        <h1>You are all set!</h1>
        <br />
        You will now see button <strong className="btn btn-primary">Create invoice</strong> in Billing interface of a project.
      </div>
    </div>
    <div className="row">
      <div className="col-sm-12">
        <Image src={billing} className="img-responsive center-block" />
      </div>
    </div>
    <div className="row">
      <div className="col-sm-12">
        <CostlockerLink path="/dashboard/billing-outlook" title="Cool, take me back to Costlocker!" className="btn btn-success btn-lg" />
      </div>
    </div>
  </div>;

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

const AddLinesModal = ({ title, activityTabs, addItems }) => {
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
  const getCheckedItems = items => items.filter(isChecked);
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
                  action={(e) => {
                    e.preventDefault();
                    addItems(getCheckedItems(items));
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
      items: lines.getAllPeople(costlocker.project.budget.peoplecosts),
    },
    activities: {
      id: 'activities',
      title: "Activities",
      items: lines.getAllActivities(costlocker.project.budget.peoplecosts),
    },
    expenses: {
      id: 'expenses',
      title: "Expenses",
      items: lines.getAllExpenses(costlocker.project.budget.expenses),
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
        <div className="col-sm-9">
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
        <div className="col-sm-3">
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
              <AddLinesModal title="Add people or activities" activityTabs={getActiveTabs(['people', 'activities'])} addItems={lines.addItems} />
            </div>
            <div className="btn-group">
              <AddLinesModal title="Add expenses" activityTabs={getActiveTabs(['expenses'])} addItems={lines.addItems} />
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
    </div>
    {lines.getGroupedLines().map(({ title, items }) => (
      <div key={ title } className={items.length || title === 'Other' ? 'show' : 'hide'}>
        <h4>{ title }</h4>
        {items.length ? (
        <div className="row text-muted">
          <div className="col-sm-1"><small>Quantity</small></div>
          <div className="col-sm-1"><small>Unit</small></div>
          <div className="col-sm-6"><small>Name</small></div>
          <div className="col-sm-2 text-right"><small>Price per unit</small></div>
          <div className="col-sm-2 text-right"><small>Total price</small></div>
        </div>
        ) : null}
        {items.map((line) => {
          const isLineIgnored = line.get('quantity') <= 0;
          return <div
            key={line.get('id')}
            className={isLineIgnored ? 'row form-grid bg-danger' : 'row form-grid'}
            title={isLineIgnored ? 'Line will be ignored and not imported to Fakturoid' : null}
          >

            <div className="btn-group">
              {lines.hasMultipleLines() ? (
              <Link
                title={<span className="fa fa-times text-danger" />} className="btn btn-link"
                action={lines.removeLine(line)}
              />
              ) : <span className="btn btn-link disabled"><span className="fa fa-times text-muted" /></span>}
              <Link
                title={<span className="fa fa-filter text-muted" />} className="btn btn-link"
                action={lines.convertLineToOnePiece(line)}
              />
            </div>
            <div className="col-sm-1 first">
              <input
                className="form-control text-right" type="number" step="any" required
                value={line.get('quantity')} onChange={lines.updateFieldInLine('quantity', line)}
              />
            </div>
            <div className="col-sm-1">
              <input
                className="form-control" type="text"
                value={line.get('unit')} onChange={lines.updateFieldInLine('unit', line)}
              />
            </div>
            <div className="col-sm-6">
              <input
                className="form-control" type="text" required
                value={line.get('name')} onChange={lines.updateFieldInLine('name', line)}
              />
            </div>
            <div className="col-sm-2 text-right">
              <input
                className="form-control text-right" type="number" step="any" required size="10"
                value={roundNumber(line.get('unit_amount'))} onChange={lines.updateFieldInLine('unit_amount', line)}
              />
            </div>
            <div className="col-sm-2 text-right">
              <input
                className="form-control text-right" type="number" step="any" required
                disabled value={roundNumber(line.get('total_amount'))}
              />
            </div>
          </div>;
        })}
        {title === 'Other' ? (
          <div className="row">
            <div className="col-sm-2">
              <br />
              <Link title="Add new empty line" action={lines.addEmptyLine()} className="btn btn-success" />
            </div>
          </div>
        ) : null}
      </div>
    ))}
    {form.get('hasVat') ? (
      <div className="form-summary">
        <div className="row">
          <div className="col-sm-4 col-sm-offset-6 text-right">
            Total amount (without VAT)
          </div>
          <div className="col-sm-2 text-right">
            <Number value={linesAmount} isElement />
          </div>
        </div>
        <div className="row">
          <div className="col-sm-4 col-sm-offset-6 text-right">
            VAT
          </div>
          <div className="col-sm-2 text-right">
            <Number value={form.get('vat')} isElement />%
          </div>
        </div>
        <div className="row">
          <div className="col-sm-4 col-sm-offset-6 text-right">
            <strong>Total amount (with VAT)</strong>
          </div>
          <div className="col-sm-2 text-right">
            <strong><Number value={linesAmount + linesAmount * (form.get('vat')) / 100} isElement /></strong>
          </div>
        </div>
      </div>
    ) : (
      <div className="form-summary">
        <div className="row">
          <div className="col-sm-4 col-sm-offset-6 text-right">
            Total amount
          </div>
          <div className="col-sm-2 text-right">
            <strong><Number value={linesAmount} isElement /></strong>
          </div>
        </div>
      </div>
    )}
    {Math.abs(billedAmount - linesAmount) <= 0.1 ? (
      <div className="row">
        <div className="col-sm-2 col-sm-offset-10">
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
  const buildSubnav = (content, className) => {
    return <PageWithSubnav
      tabs={[
        {
          id: 'invoice',
          name: 'New invoice',
          content: () => <div className="row">
            <div className={className || "col-sm-10 col-sm-offset-1"}>
              {props.invoice.costlocker ? (
                <InvoiceDetail costlocker={props.invoice.costlocker} />
              ) : null}
              {content}
            </div>
          </div>,
        },
        {
          id: 'project',
          name: 'Previously imported invoices',
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
  } else if (invoice.status === 'UNKNOWN') {
    return buildSubnav(
      <InvoiceTutorial
        header={<Errors
          title="Unknown billing"
          error="Billing not found in Costlocker, or you aren't authorized to see the project"
          errorClassName="warning"
        />}
      />,
      'col-sm-12'
    );
  }
  return buildSubnav(<InvoiceTutorial />, 'col-sm-12');
}
