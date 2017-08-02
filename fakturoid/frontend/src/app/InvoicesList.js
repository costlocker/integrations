import React from 'react';
import moment from 'moment';
import { ExternalLink, roundNumber, RadioButtons } from '../ui/Components';
import { Logo } from '../ui/Images';
import { trans } from '../i18n';

export default function InvoicesList({ invoices, subjects, dateFormat, form, isLastCreatedInvoice }) {
  return <div className={form.get('isSearching') ? 'reloading' : null}>
    <form className="form row">
      <div className="col-sm-6">
        <div className="form-group">
          <RadioButtons
            items={['', 'invoice', 'proforma.full', 'proforma.partial'].map(
              id => ({ id: id, title: trans(`invoiceTypes.${id}`) })
            )}
            isActive={type => form.get('type') === type.id}
            onChange={form.set('type')}
          />
        </div>
      </div>
      <div className="col-sm-3 col-sm-offset-3">
        <div className="form-group">
          <div className="input-group">
            <span className="input-group-addon" id="basic-addon1"><i className="fa fa-search" /></span>
            <input
              type="text" className="form-control" placeholder={trans('search.query')} id="query"
              value={form.get('query')} onChange={form.set('query')}
            />
          </div>
        </div>
      </div>
    </form>
    <br /><br />
    {invoices.length ? (
      <table className="table table-striped table-valign">
        <thead>
          <tr>
            <th>{trans('invoices.client')}</th>
            <th>{trans('invoices.id')}</th>
            <th>{trans('invoices.issued')}</th>
            <th>{trans('invoices.price')}</th>
            <th>{trans('invoices.priceVAT')}</th>
            <th>{trans('invoices.links')}</th>
          </tr>
        </thead>
        <tbody>
          {invoices.map((invoice) => (
            <tr key={invoice.id} className={isLastCreatedInvoice(invoice.id) ? 'highlight' : ''}>
              <td>
                {invoice.costlocker.project.client.name}<br />
                <small className="text-muted">{trans(`invoiceTypes.${invoice.fakturoid.type}`)}</small>
              </td>
              <td>{invoice.fakturoid.number}</td>
              <td>{moment(invoice.fakturoid.issuedAt).format(dateFormat)}</td>
              <th>{roundNumber(invoice.fakturoid.amount)}</th>
              <td>{
                invoice.fakturoid.hasVat
                  ? <strong>{roundNumber(invoice.fakturoid.amountWithVat)}</strong>
                  : <small className="text-muted">N/A</small>
              }</td>
              <td title={invoice.costlocker.update.error}>
                <ExternalLink url={invoice.costlocker.link}
                  className={invoice.costlocker.update.hasFailed ? "text-danger first" : "text-primary first"}
                  title={<span><Logo app="costlocker" color="blue" />{trans('invoices.linkProject')}</span>} />
                &nbsp;
                <ExternalLink url={invoice.fakturoid.link} className="text-success"
                  title={<span><Logo app="fakturoid" />{trans('invoices.linkInvoice')}</span>} />
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    ) : (
        <p className="text-muted">{trans('invoices.noResults')}</p>
      )}
  </div>;
}
