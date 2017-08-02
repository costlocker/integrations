import React from 'react';
import DatePicker from 'react-datepicker';
import moment from 'moment';
import 'react-datepicker/dist/react-datepicker.css';

import { Button, Link, Errors, roundNumber, Number, ExternalLink, FakturoidLink, CostlockerLink, RadioButtons } from '../ui/Components';
import { Image, Logo } from '../ui/Images';
import { PageWithSubnav } from '../ui/App';
import { isDevelopmentMode } from '../config';
import { CenteredModal } from '../ui/Modals';
import { trans } from '../i18n';
import billing from '../images/billing.png';
import Form from './Form';

const InvoiceTutorial = () =>
  <div>
    <div className="row">
      <div className="col-sm-12">
        <h1>{ trans('tutorial.title') }</h1>
        <br />
        { trans('tutorial.description', { costlockerButton: <strong className="btn btn-primary">Create invoice</strong> }) }
      </div>
    </div>
    <div className="row">
      <div className="col-sm-12">
        <Image src={billing} className="img-responsive center-block" />
      </div>
    </div>
    <div className="row">
      <div className="col-sm-12">
        <CostlockerLink path="/dashboard/billing-outlook" title={ trans('tutorial.linkCostlocker') } className="btn btn-success btn-lg" />
      </div>
    </div>
  </div>;

const ImportedInvoice = ({ invoice, forceUpdate }) =>
  <div className="text-center">
    <div className="row">
      <div className="col-sm-12">
        <h1>{ trans('createdInvoice.title') }</h1>
        <br />
        <p>{ trans('createdInvoice.description') }</p>
      </div>
    </div>
    <div className="row">
      <div className="col-sm-12">
        <ExternalLink url={invoice.fakturoid.link} title={ trans('createdInvoice.linkFakturoid') } className="btn btn-primary" />
        <small className="text-muted ps-10">{ trans('createdInvoice.linkSeparator') }</small>
        <ExternalLink url={invoice.costlocker.link} title={ trans('createdInvoice.linkCostlocker') } className="btn btn-default" />
      </div>
    </div>
    {isDevelopmentMode &&
    <div>
      <hr />
      <Button
        title="Force create (DEV)"
        className="btn btn-warning"
        action={forceUpdate}
      />
    </div>
    }
  </div>;

const InvoiceDetail = ({ costlocker }) => (
  <div>
    <h1>
      { trans('page.invoice') }
      {costlocker.project.project_id.id ? <span> / {costlocker.project.project_id.id}</span> : null}
    </h1>
    <p>
      <strong>{costlocker.project.name}</strong>, <span>{costlocker.project.client.name}</span>
    </p>
  </div>
);

const AddLinesModal = ({ type, title, activityTabs, addItems, editorForm }) => {
  const hasMultipleTabs =  activityTabs.length > 1;
  const isActive = type => hasMultipleTabs
    ? type.id === (editorForm.get('activeTab') || activityTabs[0].id)
    : true;
  return <CenteredModal
    type={type}
    onOpen={() => editorForm.update(
      editor => editor
        .setIn(['activeTab'], '')
        .setIn(['checkedIds'], editorForm.get('checkedIds').clear())
    )}
    link={{
      title:  title,
      className: "btn btn-primary",
    }}
    content={
      (closeModal) =>
        <div>
          <h2>{title}</h2>
          {hasMultipleTabs ? (
          <RadioButtons
            items={activityTabs}
            isActive={isActive}
            onChange={editorForm.set('activeTab')}
            className="btn-group-justified"
          />
          ) : null}
          {activityTabs.map(type => {
            const items = type.items();
            return <div key={type.id} className={isActive(type) ? 'show' : 'hide'}>
              <form className="form">
                {items.map(item => (
                  <label key={item.id} className="checkbox">
                    <input
                      type="checkbox"
                      checked={editorForm.isChecked('checkedIds', item.id)}
                      onChange={editorForm.checkItem('checkedIds')} value={item.id}
                    />
                    {item.name}
                  </label>
                ))}
                <Link
                  title={ trans('invoiceModal.checkAll', { count: items.length}) }
                  action={editorForm.checkAll('checkedIds', items.map(item => item.id))} />
                <br /><br />
                <Button
                  title={ trans('invoiceModal.submit') }
                  action={(e) => {
                    e.preventDefault();
                    const checkedItems = items.filter(item => editorForm.isChecked('checkedIds', item.id));
                    addItems(checkedItems);
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

const InvoiceEditor = ({ fakturoidSubjects, costlocker, form, lines, reloadSubjects, dateFormat }) => {
  lines.addDefaultIfIsEmpty({
    name: costlocker.billing.billing.description
      ? costlocker.billing.billing.description
      : costlocker.project.name,
    amount: costlocker.billing.billing.total_amount
  })

  const linesAmount = lines.calculateTotaAmount();
  const billedAmount = costlocker.maxBillableAmount;
  const isRevenueGreaterThanBilling = (billedAmount - linesAmount) >= -0.1;
  const vat = lines.calculateVat();

  const editorForm = new Form(['editor']);
  const advancedSettingsLink = (direction) =>
    <Link
      title={<span>{ trans(`editor.advancedSettings.${direction}`) } <span className={`fa fa-arrow-${direction}`} /></span>}
      className={`btn btn-horizontal ${direction}`}
      action={editorForm.toggle('hasAdvancedSettings')} />;

  const activityTabs = {
    people: {
      id: 'people',
      title: trans('invoiceLines.people'),
      summary: trans('invoiceLines.peopleAndActivites'),
      icon: 'fa-users',
      items: lines.getAllPeople(costlocker.project.budget.peoplecosts),
    },
    activities: {
      id: 'activities',
      title: trans('invoiceLines.activities'),
      icon: 'fa-users',
      items: lines.getAllActivities(costlocker.project.budget.peoplecosts),
    },
    expenses: {
      id: 'expenses',
      title: trans('invoiceLines.expenses'),
      icon: 'fa-pie-chart',
      items: lines.getAllExpenses(costlocker.project.budget.expenses),
    },
    discount: {
      icon: 'fa-percent',
      title: trans('invoiceLines.discount'),
    },
    other: {
      icon: 'fa-gear',
      title: trans('invoiceLines.other'),
    },
  };
  const getActiveTabs = visibleTabs => visibleTabs.map(id => activityTabs[id]);

  const issuedAt = moment(form.get('issuedAt'));
  const dueDate = moment(issuedAt).add(form.get('due'), 'days');

  return <form className="form" onSubmit={form.submit}>
    <div className="form-group">
      <label htmlFor="type">{ trans('editor.type') }</label><br />
      <RadioButtons
        items={['invoice', 'proforma.full', 'proforma.partial'].map(
          id => ({ id: id, title: trans(`invoiceTypes.${id}`) })
        )}
        isActive={type => form.get('type') === type.id}
        onChange={form.set('type')}
      />
    </div>
    <div className="form-group">
      <label htmlFor="fakturoidSubject">{ trans('editor.subject') }</label>
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
          <FakturoidLink
            title={<span className="text-success"><Logo app="fakturoid" /> { trans('editor.createSubject') }</span>}
            path="/subjects/new" className="btn btn-link"
          />
        </div>
      </div>
      <p className="help-block">
        { trans('editor.outdatedSubjects') } <Link
          action={reloadSubjects}
          title={<span><i className="fa fa-refresh" /> { trans('editor.reloadSubjects') }</span>}
          className="btn btn-link"
        />
      </p>
    </div>
    <div className="row">
      <div className="col-sm-4">
        <div className="form-group">
          <label htmlFor="issuedAt">{ trans('editor.issuedAt') }</label>
          <DatePicker
            dateFormat={dateFormat}
            className="form-control"
            selected={issuedAt}
            onChange={date => form.set('issuedAt')({
              target: {
                type: 'value',
                value: date.format('YYYY-MM-DD')
              }
            })}
          />
        </div>
      </div>
      <div className="col-sm-3">
        <div className="form-group">
          <label htmlFor="due">{ trans('editor.due') }</label>
          <input
            className="form-control" type="number" id="due" min="1" max="100" step="1"
            value={form.get('due')} onChange={form.set('due')}
          />
        </div>
      </div>
      <div className="col-sm-3">
        <div className="form-group">
          <label>&nbsp;</label><br />
          <span className="btn btn-link" disabled>{dueDate.format(`dddd, ${dateFormat}`)}</span>
        </div>
      </div>
    </div>
    {editorForm.get('hasAdvancedSettings') ? (
      <div>
        <div className="well">
          <div className="form-group">
            <label htmlFor="orderNumber">{ trans('editor.orderNumber') }</label>
            <input
              type="text" className="form-control" name="orderNumber" id="orderNumber"
              value={form.get('orderNumber')} onChange={form.set('orderNumber')}
            />
          </div>
          <div className="form-group">
            <label htmlFor="noteBeforeLines">{ trans('editor.noteBeforeLines') }</label>
            <textarea
              className="form-control" name="noteBeforeLines" id="noteBeforeLines" rows="4"
              value={form.get('noteBeforeLines')} onChange={form.set('noteBeforeLines')}
            >
            </textarea>
          </div>
        </div>
        {advancedSettingsLink('up')}
      </div>
    ) : (
      advancedSettingsLink('down')
    )}
    <div className="form-group">
      <div className="row">
        <div className="col-sm-10">
          <div className="btn-toolbar">
            <div className="btn-group">
              <AddLinesModal
                editorForm={editorForm}
                type="activities" title={<span><span className={`fa ${activityTabs.people.icon}`} /> { trans('invoiceLines.actions.peopleAndActivites') }</span>}
                activityTabs={getActiveTabs(['people', 'activities'])} addItems={lines.addItems} />
            </div>
            <div className="btn-group">
              <AddLinesModal
                editorForm={editorForm}
                type="expenses" title={<span><span className={`fa ${activityTabs.expenses.icon}`} /> { trans('invoiceLines.actions.expenses') }</span>}
                activityTabs={getActiveTabs(['expenses'])} addItems={lines.addItems} />
            </div>
            <div className="btn-group">
              <Link
                title={<span><span className={`fa ${activityTabs.discount.icon}`} /> { trans('invoiceLines.actions.discount') }</span>}
                action={lines.addDiscount(costlocker.project.budget.discount, trans('invoiceLines.discount'))}
                className={`btn btn-primary ${costlocker.project.budget.discount ? '' : 'disabled'}`} />
            </div>
          </div>
        </div>
        <div className="col-sm-2">
          <div className="btn-toolbar">
            <div className="btn-group-justified">
              <Link
                title={<span><span className="fa fa-refresh" /> { trans('invoiceLines.actions.reset') }</span>}
                action={lines.removeAllLines()} className="btn btn-default" />
            </div>
          </div>
        </div>
      </div>
    </div>
    <br />
    {lines.getGroupedLines().map(({ id, items }) => (
      <div key={ id } className={items.length || id === 'other' ? 'show' : 'hide'}>
        <h4><span className={`fa ${activityTabs[id].icon}`} /> { activityTabs[id].summary || activityTabs[id].title }</h4>
        {items.length ? (
        <div className="row text-muted">
          <div className="col-sm-1"><small>{ trans('invoiceLines.item.quantity') }</small></div>
          <div className="col-sm-1"><small>{ trans('invoiceLines.item.unit') }</small></div>
          <div className={lines.hasVat ? "col-sm-5" : "col-sm-6"}><small>{ trans('invoiceLines.item.name') }</small></div>
          {lines.hasVat ? <div className="col-sm-1"><small>{ trans('invoiceLines.item.vat') }</small></div> : null}
          <div className="col-sm-2 text-right"><small>{ trans('invoiceLines.item.unit_amount') }</small></div>
          <div className="col-sm-2 text-right"><small>{ trans('invoiceLines.item.total_amount') }</small></div>
        </div>
        ) : null}
        {items.map((line) => {
          const isLineIgnored = line.get('quantity') <= 0;
          const cssPrice = id === 'discount' ? "form-control text-right text-danger" : "form-control text-right";
          return <div
            key={line.get('id')}
            className={isLineIgnored ? 'row form-grid bg-danger' : 'row form-grid'}
            title={isLineIgnored ? trans('invoiceLines.item.invalid') : null}
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
            <div className={lines.hasVat ? "col-sm-5" : "col-sm-6"}>
              <input
                className="form-control" type="text" required
                value={line.get('name')} onChange={lines.updateFieldInLine('name', line)}
              />
            </div>
            {lines.hasVat ? (
            <div className="col-sm-1">
              <input
                className="form-control" type="text"
                value={line.get('vat')} onChange={lines.updateFieldInLine('vat', line)}
              />
            </div>
            ) : null}
            <div className="col-sm-2 text-right">
              <input
                className={cssPrice} type="text" step="any" required
                value={roundNumber(line.get('unit_amount'))} onChange={lines.updateFieldInLine('unit_amount', line)}
              />
            </div>
            <div className="col-sm-2 text-right">
              <input
                className={cssPrice} type="number" step="any" required
                disabled value={roundNumber(line.get('total_amount'))}
              />
            </div>
          </div>;
        })}
        {id === 'other' ? (
          <div className="row">
            <div className="col-sm-2">
              <br />
              <Link
                title={<span><span className="fa fa-plus-circle" /> { trans('invoiceLines.actions.newLine') }</span>}
                action={lines.addEmptyLine()} className="btn btn-success" />
            </div>
          </div>
        ) : null}
      </div>
    ))}
    <div className="form-summary">
      {!isRevenueGreaterThanBilling ? (
        <Errors
          title={trans('editor.revenueError.title', { billedAmount: roundNumber(billedAmount) }) }
          error={trans('editor.revenueError.error', { linesAmount: roundNumber(linesAmount) }) }
          errorClassName="warning"
        />
      ) : null}
      {lines.hasVat ? (
        <div>
          <div className="row">
            <div className="col-sm-4 col-sm-offset-6 text-right">
              { trans('summary.totalWithoutVAT') }
            </div>
            <div className="col-sm-2 text-right">
              <Number value={linesAmount} isElement />
            </div>
          </div>
          {Object.keys(vat.rates).map(rate => (
          <div key={rate} className="row">
            <div className="col-sm-4 col-sm-offset-6 text-right">
              { trans('summary.vat') } (<Number value={rate} isElement />%)
            </div>
            <div className="col-sm-2 text-right">
              <Number value={vat.rates[rate]} isElement />
            </div>
          </div>
          ))}
          <div className="row">
            <div className="col-sm-4 col-sm-offset-6 text-right">
              <strong>{ trans('summary.totalWithVAT') }</strong>
            </div>
            <div className="col-sm-2 text-right">
              <strong><Number value={linesAmount + vat.total} isElement /></strong>
            </div>
          </div>
        </div>
      ) : (
        <div>
          <div className="row">
            <div className="col-sm-4 col-sm-offset-6 text-right">
              { trans('summary.total') }
            </div>
            <div className="col-sm-2 text-right">
              <strong><Number value={linesAmount} isElement /></strong>
            </div>
          </div>
        </div>
      )}
    </div>
    <div className="row">
      <div className="col-sm-2 col-sm-offset-10">
        {isRevenueGreaterThanBilling ? (
          <button type="submit" className="btn btn-primary btn-block">{ trans('editor.submit') }</button>
        ) : (
          <span className="btn btn-danger btn-block" disabled title="Update total amount">{ trans('editor.submit') }</span>
        )}
      </div>
    </div>
    <div className="form-group">
      <label htmlFor="note">{ trans('editor.note.title') }</label>
      <textarea
        className="form-control" name="note" id="note" rows="4"
        placeholder={ trans('editor.note.placeholder') }
        value={form.get('note')} onChange={form.set('note')}
      >
      </textarea>
    </div>
  </form>;
}

const InvoicesPages = ({ props, content, header, className }) =>
  <PageWithSubnav
    tabs={[
      {
        id: 'new',
        name: trans('page.invoice'),
        content: () => <div className="row">
          <div className={className}>
            {header}
            {content}
          </div>
        </div>,
      },
      {
        id: 'invoices',
        name: trans('page.invoicesHistory'),
        content: () => props.invoices,
      },
    ]}
  />;

export default function Invoice(props) {
  const invoice = props.invoice;
  const buildInvoice = (content) =>
    <InvoicesPages
      props={props} content={content} className="col-sm-10 col-sm-offset-1"
      header={<InvoiceDetail costlocker={props.invoice.costlocker} />}
    />;
  const buildTutorial = (header) =>
    <InvoicesPages
      props={props} content={<InvoiceTutorial />} className="col-sm-12 text-center"
      header={header}
  />;

  if (
    invoice.status === 'READY' || invoice.status === 'NEW' ||
    (invoice.status === 'ALREADY_IMPORTED' && props.form.get('isForced'))
  ) {
    return buildInvoice(<InvoiceEditor {...props} costlocker={props.invoice.costlocker} />);
  } else if (invoice.status === 'NOT_DRAFT') {
    return buildInvoice(<Errors { ...trans('invalidInvoice.notDraft') } />);
  } else if (invoice.status === 'ALREADY_IMPORTED') {
    return <InvoicesPages props={props} content={<ImportedInvoice {...props} />} className="col-sm-12" />;
  } else if (invoice.status === 'UNKNOWN') {
    return buildTutorial(<Errors { ...trans('invalidInvoice.unknownBilling') } errorClassName="warning" />
  );
  }
  return buildTutorial();
}
