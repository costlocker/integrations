import React from 'react';
import { appHost } from '../config';
import InvoicesList from './InvoicesList';
import { PageWithSubnav } from '../ui/App';

export default function InvoiceTutorial({ latestInvoices, subjects, renderContent }) {
  const billingOutlookUrl = `${appHost}/dashboard/billing-outlook`;
  return <PageWithSubnav
    tabs={[
      {
        id: 'tutorial',
        name: 'Tutorial',
        content: () => <ol>
          <li>Go to <a href={billingOutlookUrl}>Billing Outlook in Costlocker</a></li>
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
