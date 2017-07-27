import React from 'react';
import { ExternalLink, roundNumber } from '../ui/Components';
import { appState } from '../state';

const isHighlighted = id => id === appState.cursor(['app', 'lastCreatedInvoice']).deref();

export default function InvoicesList({ invoices, subjects }) {
  const filter = appState.cursor(['search']);
  const setFilter = (field) => (e) => {
    appState.cursor(['search']).set(field, e.target.value);
  };
  return <div>
    <form className="form row">
      <div className="col-sm-5">
        <div className="form-group">
          <div className="btn-group">
            {[
              { title: 'All', id: '' },
              { title: 'Standard', id: 'invoice' },
              { title: 'Proforma (full)', id: 'proforma.full' },
              { title: 'Proforma (partial)', id: 'proforma.partial' },
            ].map(type => (
              <label key={type.id} className={filter.get('type') === type.id ? 'btn btn-primary active' : 'btn btn-default'}>
                <input
                  type="radio" name="type" value={type.id} className="hide"
                  checked={filter.get('type') === type.id} onChange={setFilter('type')} /> {type.title}
              </label>
            ))}
          </div>
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
      <table className="table table-striped">
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
                <small className="text-muted">{invoice.fakturoid.type}</small>
              </td>
              <td>{invoice.fakturoid.number}</td>
              <td>{invoice.fakturoid.issuedAt}</td>
              <th>{invoice.costlocker.billing.billing.total_amount}</th>
              <th>{roundNumber(invoice.fakturoid.amount)}</th>
              <td>
                <ExternalLink url={invoice.costlocker.link} title="Open project" />
                &nbsp;
                <ExternalLink url={invoice.fakturoid.link} title="Open invoice" />
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
