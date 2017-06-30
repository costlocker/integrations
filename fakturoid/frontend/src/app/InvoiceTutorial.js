import React from 'react';
import { appHost } from '../config';
import InvoicesList from './InvoicesList';

export default function InvoiceTutorial({ latestInvoices, subjects }) {
  const billingOutlookUrl = `${appHost}/dashboard/billing-outlook`
  return <div>
    <ol>
      <li>Go to <a href={billingOutlookUrl}>Billing Outlook in Costlocker</a></li>
      <li>Go to selected project invoice</li>
      <li>Click on <strong>Create invoice</strong></li>
    </ol>
    <h2>Latest invoices</h2>
    <InvoicesList invoices={latestInvoices} subjects={subjects} />
  </div>;
}
