import React from 'react';
import InvoicesList from './InvoicesList';
import { PageWithSubnav } from '../ui/App';
import {  CostlockerLink, Image } from '../ui/Components';

export default function InvoiceTutorial({ latestInvoices, subjects, renderContent }) {
  return <PageWithSubnav
    tabs={[
      {
        id: 'tutorial',
        name: 'Tutorial',
        content: () => <div>
          <div className="row">
            <div className="col-sm-6">
              <h4>
                1. Select an invoice <CostlockerLink path="/dashboard/billing-outlook" title="in Billing Outlook" />
              </h4>
              <Image src="https://user-images.githubusercontent.com/7994022/27791724-c9e37ad4-5ff6-11e7-8f8b-f333377e060e.png" />
            </div>
            <div className="col-sm-6">
              <h4>
                2. Click on <strong className="btn btn-primary disabled">Create invoice</strong> in Billing
              </h4>
              <Image src="https://user-images.githubusercontent.com/7994022/27791822-33f0cbf2-5ff7-11e7-93fb-5eb26e6e937a.png" />
            </div>
          </div>
          <div className="row">
            <div className="col-sm-6">
              <h4>
                3. Fill the invoice details and import the invoice to Fakturoid
              </h4>
              <Image src="https://user-images.githubusercontent.com/7994022/27791943-badacd16-5ff7-11e7-8d4f-52fe95902aa3.png" />
            </div>
            <div className="col-sm-6">
              <h4>
                3. Check the invoice in Fakturoid
              </h4>
              <Image src="https://user-images.githubusercontent.com/7994022/27791986-ebf0c3ce-5ff7-11e7-925a-96e99ec8de63.png" />
            </div>
          </div>
        </div>,
      },
      {
        id: 'invoices',
        name: 'Latest invoices',
        content: () => <InvoicesList invoices={latestInvoices} subjects={subjects} />,
      },
    ]}
  />;
}
