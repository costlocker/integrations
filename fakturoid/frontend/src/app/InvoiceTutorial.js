import React from 'react';
import InvoicesList from './InvoicesList';
import { PageWithSubnav } from '../ui/App';
import {  CostlockerLink } from '../ui/Components';

export default function InvoiceTutorial({ latestInvoices, subjects, renderContent }) {
  return <PageWithSubnav
    tabs={[
      {
        id: 'tutorial',
        name: 'Tutorial',
        content: () => <ol>
          <li>Go to <CostlockerLink path="/dashboard/billing-outlook" title="Billing Outlook in Costlocker" /></li>
          <li>Go to selected project invoice</li>
          <li>Click on <strong>Create invoice</strong></li>
        </ol>,
      },
      {
        id: 'invoices',
        name: 'Latest invoices',
        content: () => <InvoicesList invoices={latestInvoices} subjects={subjects} />,
      },
    ]}
  />;
}
