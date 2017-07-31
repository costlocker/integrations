import React from 'react';
import { ExternalLink, roundNumber, RadioButtons } from '../ui/Components';
import { Logo } from '../ui/Images';
import { appState } from '../state';

const isHighlighted = id => id === appState.cursor(['app', 'lastCreatedInvoice']).deref();

export default function InvoicesList({ invoices, subjects }) {
  const filter = appState.cursor(['search']);
  const setFilter = (field) => (e) => {
    appState.cursor(['search']).set(field, e.target.value);
  };
  const invoiceTypes = {
    '': 'All',
    'invoice': 'Standard',
    'proforma.full': 'Proforma (full)',
    'proforma.partial': 'Proforma (partial)',
  };
  return <div>
    <form className="form row">
      <div className="col-sm-5">
        <div className="form-group">
          <RadioButtons
            items={['', 'invoice', 'proforma.full', 'proforma.partial'].map(
              id => ({ id: id, title: invoiceTypes[id] })
            )}
            isActive={type => filter.get('type') === type.id}
            onChange={setFilter('type')}
          />
        </div>
      </div>
      <div className="col-sm-3 col-sm-offset-4">
        <div className="form-group">
          <div className="input-group">
            <span className="input-group-addon" id="basic-addon1"><i className="fa fa-search" /></span>
            <input
              type="text" className="form-control" placeholder="Search" id="query"
              value={filter.get('query')} onChange={setFilter('query')}
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
            <th>Client</th>
            <th>ID</th>
            <th>Issued</th>
            <th>Price</th>
            <th>Price with VAT</th>
            <th>Links</th>
          </tr>
        </thead>
        <tbody>
          {invoices.map((invoice) => (
            <tr key={invoice.id} className={isHighlighted(invoice.id) ? 'highlight' : ''}>
              <td>
                {invoice.costlocker.project.client.name}<br />
                <small className="text-muted">{invoiceTypes[invoice.fakturoid.type]}</small>
              </td>
              <td>{invoice.fakturoid.number}</td>
              <td>{invoice.fakturoid.issuedAt}</td>
              <th>{invoice.costlocker.billing.billing.total_amount}</th>
              <th>{roundNumber(invoice.fakturoid.amount)}</th>
              <td>
                <ExternalLink url={invoice.costlocker.link} className="text-primary first"
                  title={<span><Logo app="costlocker" color="blue" />Open project</span>} />
                &nbsp;
                <ExternalLink url={invoice.fakturoid.link} className="text-success"
                  title={<span><Logo app="fakturoid" />Open invoice</span>} />
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    ) : (
      <p className="text-muted">No invoices found</p>
    )}
  </div>;
}
